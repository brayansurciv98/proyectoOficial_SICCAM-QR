#!/usr/bin/env python3
"""SICCAM-QR - Lector de códigos QR (Raspberry Pi 4 + cámara USB).

- Hilo "captura": lee la cámara a su máxima velocidad y arma el video. Nunca espera a nadie.
- Hilo "lector": busca QR en el cuadro más reciente (si tarda, salta cuadros; el video no se frena).
  Lee varios QR a la vez; cada estudiante se envía una sola vez por día.
- Cada lectura se guarda en una cola SQLite y otro hilo la envía a Laravel
  (si no hay red, se guarda y se sincroniza después con la hora real de lectura).
- http://IP_DE_LA_PI:8080/        vista en vivo + estado
  http://IP_DE_LA_PI:8080/pantalla  pantalla completa para proyectar (defensa)
  http://IP_DE_LA_PI:8080/video   stream MJPEG (lo usa el panel de Laravel)
  http://IP_DE_LA_PI:8080/estado  estado en JSON
"""

import configparser
import logging
import re
import signal
import subprocess
import sys
import threading
import time
from collections import deque
from datetime import date, datetime
from pathlib import Path

import cv2
import numpy as np
from flask import Flask, Response, jsonify, request
from pyzbar import pyzbar
from pyzbar.pyzbar import ZBarSymbol
from werkzeug.serving import make_server

from cola_asistencias import ColaAsistencias, EnviadorLaravel

BASE = Path(__file__).resolve().parent
log = logging.getLogger("siccam.camara")

VERDE = (0, 200, 0)
AMARILLO = (0, 200, 255)
ROJO = (0, 0, 255)

# Cuánto tiempo se siguen dibujando los recuadros de la última lectura (segundos)
DURACION_RECUADRO = 0.6


def cargar_config():
    ruta = BASE / "config.ini"
    if not ruta.exists():
        sys.exit("Falta config.ini: copie config.ini.ejemplo como config.ini y edítelo.")
    cfg = configparser.ConfigParser(interpolation=None)
    cfg.read(ruta, encoding="utf-8")
    return cfg


def reloj_sincronizado():
    """True si la hora vino de NTP o de la laptop (sincronizar_hora.sh); None si no se puede saber."""
    if Path("/run/siccam-hora-ok").exists():
        return True
    try:
        salida = subprocess.run(
            ["timedatectl", "show", "-p", "NTPSynchronized", "--value"],
            capture_output=True, text=True, timeout=3,
        ).stdout.strip()
        return salida == "yes" if salida else None
    except Exception:
        return None


class Estado:
    """Datos compartidos entre la cámara, el lector, el envío y el servidor web."""

    def __init__(self):
        self.lock = threading.Lock()
        self.cond = threading.Condition()
        self.camara_ok = False
        self.fps_camara = 0.0
        self.fps_lectura = 0.0
        self.reloj_ok = None
        self.dia = date.today()
        self.enviados_hoy = set()      # códigos ya puestos en cola hoy
        self.resultado = {}            # código -> texto para mostrar (PRESENTE, TARDANZA...)
        self.nombres = {}              # código -> nombre del estudiante (lo devuelve Laravel)
        self.ultimas = deque(maxlen=12)
        self.detecciones = []          # recuadros de la última lectura: (x, y, w, h, texto, color)
        self.detecciones_t = 0.0
        self.jpeg = None               # video con la barra de estado (panel de Laravel)
        self.frame_id = 0
        self.clientes_video = 0
        self.jpeg_limpio = None        # video sin barra (vista /pantalla)
        self.frame_id_limpio = 0
        self.clientes_limpio = 0

    def nuevo_dia_si_corresponde(self):
        hoy = date.today()
        if self.dia != hoy:
            self.dia = hoy
            self.enviados_hoy.clear()
            self.resultado.clear()

    def actualizar_lectura(self, codigo, texto, nombre=None):
        with self.lock:
            self.resultado[codigo] = texto
            if nombre:
                self.nombres[codigo] = nombre
            for lectura in self.ultimas:
                if lectura["codigo"] == codigo:
                    lectura["estado"] = texto
                    if nombre:
                        lectura["nombre"] = nombre
                    break

    def publicar_jpeg(self, jpeg, limpio=False):
        with self.cond:
            if limpio:
                self.jpeg_limpio = jpeg
                self.frame_id_limpio += 1
            else:
                self.jpeg = jpeg
                self.frame_id += 1
            self.cond.notify_all()

    def hay_espectadores(self):
        return self.clientes_video > 0 or self.clientes_limpio > 0


class Captura(threading.Thread):
    """Lee la cámara lo más rápido posible y arma el video. No busca QR."""

    def __init__(self, cfg, estado, cola, enviador, detener):
        super().__init__(name="captura", daemon=True)
        self.estado = estado
        self.cola = cola
        self.enviador = enviador
        self.detener = detener

        self.indice = cfg.getint("camara", "indice", fallback=0)
        self.ancho = cfg.getint("camara", "ancho", fallback=1280)
        self.alto = cfg.getint("camara", "alto", fallback=720)
        self.fps_pedidos = cfg.getint("camara", "fps", fallback=30)
        self.formato = cfg.get("camara", "formato", fallback="MJPG").strip()

        self.stream_activo = cfg.getboolean("stream", "activo", fallback=True)
        self.intervalo_stream = 1.0 / max(1, cfg.getint("stream", "fps_max", fallback=12))
        self.calidad_jpeg = cfg.getint("stream", "calidad_jpeg", fallback=70)

        self.cap = None
        self.cond_cuadro = threading.Condition()
        self.cuadro = None
        self.cuadro_id = 0
        self._ultimo_jpeg = 0.0
        self._ultimo_reloj = 0.0

    # ---------- cámara ----------
    def abrir_camara(self):
        cap = cv2.VideoCapture(self.indice, cv2.CAP_V4L2)
        if not cap.isOpened():
            cap = cv2.VideoCapture(self.indice)
        if not cap.isOpened():
            return None

        if self.formato:
            cap.set(cv2.CAP_PROP_FOURCC, cv2.VideoWriter_fourcc(*self.formato[:4]))
        cap.set(cv2.CAP_PROP_FRAME_WIDTH, self.ancho)
        cap.set(cv2.CAP_PROP_FRAME_HEIGHT, self.alto)
        cap.set(cv2.CAP_PROP_FPS, self.fps_pedidos)
        cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)  # siempre el cuadro más reciente

        fourcc = int(cap.get(cv2.CAP_PROP_FOURCC))
        formato_real = "".join(chr((fourcc >> (8 * i)) & 0xFF) for i in range(4)) if fourcc else "?"
        log.info("Cámara %d abierta: %dx%d, formato %s, %.0f fps (según la cámara)", self.indice,
                 cap.get(cv2.CAP_PROP_FRAME_WIDTH), cap.get(cv2.CAP_PROP_FRAME_HEIGHT),
                 formato_real, cap.get(cv2.CAP_PROP_FPS))
        return cap

    def cerrar_camara(self):
        if self.cap is not None:
            self.cap.release()
            self.cap = None

    def siguiente_cuadro(self, ultimo_id, timeout=1.0):
        """Para el lector: espera un cuadro más nuevo que ultimo_id y lo devuelve."""
        with self.cond_cuadro:
            self.cond_cuadro.wait_for(
                lambda: self.cuadro_id != ultimo_id or self.detener.is_set(), timeout=timeout)
            return self.cuadro_id, self.cuadro

    def run(self):
        fallas = 0
        cuadros = 0
        inicio_fps = time.monotonic()

        while not self.detener.is_set():
            self.revisar_reloj()

            if self.cap is None:
                self.cap = self.abrir_camara()
                if self.cap is None:
                    self.estado.camara_ok = False
                    log.error("No se pudo abrir la cámara %d; reintento en 3 s", self.indice)
                    self.publicar_aviso("CAMARA DESCONECTADA")
                    self.detener.wait(3)
                    continue

            ok, frame = self.cap.read()
            if not ok or frame is None:
                fallas += 1
                if fallas >= 30:
                    log.warning("La cámara dejó de enviar imagen; reabriendo")
                    self.estado.camara_ok = False
                    self.cerrar_camara()
                    fallas = 0
                    self.detener.wait(2)
                else:
                    time.sleep(0.03)
                continue

            fallas = 0
            self.estado.camara_ok = True

            # Entregar el cuadro al lector de QR (sin esperarlo)
            with self.cond_cuadro:
                self.cuadro = frame
                self.cuadro_id += 1
                self.cond_cuadro.notify_all()

            cuadros += 1
            transcurrido = time.monotonic() - inicio_fps
            if transcurrido >= 1:
                self.estado.fps_camara = round(cuadros / transcurrido, 1)
                cuadros = 0
                inicio_fps = time.monotonic()

            self.publicar_stream(frame)

        self.cerrar_camara()
        with self.cond_cuadro:
            self.cond_cuadro.notify_all()

    # ---------- video ----------
    def publicar_stream(self, frame):
        # Solo se codifica JPEG si alguien está viendo, y como máximo fps_max por segundo
        if not self.stream_activo or not self.estado.hay_espectadores():
            return
        ahora = time.monotonic()
        if ahora - self._ultimo_jpeg < self.intervalo_stream:
            return
        self._ultimo_jpeg = ahora

        imagen = frame.copy()  # el lector usa el original: aquí se dibuja sobre una copia
        with self.estado.lock:
            detecciones = list(self.estado.detecciones) if ahora - self.estado.detecciones_t < DURACION_RECUADRO else []
        for x, y, w, h, texto, color in detecciones:
            cv2.rectangle(imagen, (x, y), (x + w, y + h), color, 3)
            cv2.putText(imagen, texto, (x, max(25, y - 10)), cv2.FONT_HERSHEY_SIMPLEX, 0.7, color, 2)

        calidad = [cv2.IMWRITE_JPEG_QUALITY, self.calidad_jpeg]
        if self.estado.clientes_limpio > 0:
            ok, buffer = cv2.imencode(".jpg", imagen, calidad)
            if ok:
                self.estado.publicar_jpeg(buffer.tobytes(), limpio=True)
        if self.estado.clientes_video > 0:
            self.barra_estado(imagen)
            ok, buffer = cv2.imencode(".jpg", imagen, calidad)
            if ok:
                self.estado.publicar_jpeg(buffer.tobytes())

    def barra_estado(self, imagen):
        conectado = self.enviador.conectado
        servidor = "SERVIDOR OK" if conectado else ("SIN CONEXION" if conectado is False else "SERVIDOR ?")
        texto = "%s | PENDIENTES %d | CAM %.0f FPS | QR %.0f/s | %s" % (
            servidor, self.cola.cantidad(), self.estado.fps_camara, self.estado.fps_lectura,
            datetime.now().strftime("%H:%M:%S"))
        if self.estado.reloj_ok is False:
            texto += " | RELOJ SIN SINCRONIZAR"
        cv2.rectangle(imagen, (0, 0), (imagen.shape[1], 32), (0, 0, 0), -1)
        cv2.putText(imagen, texto, (10, 22), cv2.FONT_HERSHEY_SIMPLEX, 0.6,
                    VERDE if conectado else AMARILLO, 2)

    def publicar_aviso(self, texto):
        if not self.stream_activo or not self.estado.hay_espectadores():
            return
        imagen = np.zeros((self.alto // 2, self.ancho // 2, 3), dtype=np.uint8)
        cv2.putText(imagen, texto, (30, imagen.shape[0] // 2), cv2.FONT_HERSHEY_SIMPLEX, 1.0, ROJO, 2)
        ok, buffer = cv2.imencode(".jpg", imagen)
        if ok:
            self.estado.publicar_jpeg(buffer.tobytes())
            self.estado.publicar_jpeg(buffer.tobytes(), limpio=True)

    def revisar_reloj(self):
        ahora = time.monotonic()
        if ahora - self._ultimo_reloj >= 300 or self._ultimo_reloj == 0:
            self._ultimo_reloj = ahora
            self.estado.reloj_ok = reloj_sincronizado()
            if self.estado.reloj_ok is False:
                log.warning("La Pi aún no tomó la hora de la laptop ni de internet: las horas pueden ser incorrectas")


class LectorQR(threading.Thread):
    """Busca QR en el cuadro más reciente. Si tarda, salta cuadros: el video no se frena."""

    def __init__(self, cfg, estado, cola, captura, detener):
        super().__init__(name="lector", daemon=True)
        self.estado = estado
        self.cola = cola
        self.captura = captura
        self.detener = detener

        # Segundos sin ver una credencial para que, si vuelve a aparecer, cuente como otra pasada
        self.pausa = cfg.getfloat("lectura", "pausa_seg", fallback=3)
        self.patron = re.compile(cfg.get("lectura", "patron_codigo", fallback=r"^EST-\d{4}-\d{4}$"))
        # 1.0 = imagen completa; 0.75 o 0.5 = busca QR más rápido (para QR cercanos a la cámara)
        self.escala = max(0.25, min(1.0, cfg.getfloat("lectura", "escala", fallback=1.0)))
        self.visto = {}  # código -> última vez que se vio frente a la cámara

    def run(self):
        ultimo = -1
        leidos = 0
        inicio = time.monotonic()

        while not self.detener.is_set():
            cuadro_id, frame = self.captura.siguiente_cuadro(ultimo)
            if frame is None or cuadro_id == ultimo:
                continue
            ultimo = cuadro_id

            try:
                self.procesar(frame)
            except Exception:
                log.exception("Error al buscar QR (se sigue con el siguiente cuadro)")

            leidos += 1
            transcurrido = time.monotonic() - inicio
            if transcurrido >= 1:
                self.estado.fps_lectura = round(leidos / transcurrido, 1)
                leidos = 0
                inicio = time.monotonic()

    def procesar(self, frame):
        gris = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
        if self.escala < 1.0:
            gris = cv2.resize(gris, None, fx=self.escala, fy=self.escala, interpolation=cv2.INTER_AREA)

        detecciones = []
        for qr in pyzbar.decode(gris, symbols=[ZBarSymbol.QRCODE]):
            texto = qr.data.decode("utf-8", errors="replace")
            codigo = texto.split("|")[0].strip()
            x, y, w, h = (int(v / self.escala) for v in qr.rect)

            if not self.patron.fullmatch(codigo):
                detecciones.append((x, y, w, h, "QR NO VALIDO", ROJO))
                continue

            situacion = self.registrar(codigo)
            with self.estado.lock:
                resultado = self.estado.resultado.get(codigo, "")

            if situacion == "nuevo":
                detecciones.append((x, y, w, h, codigo + " LEIDO", VERDE))
            elif situacion == "repetido":
                detecciones.append((x, y, w, h, codigo + " YA REGISTRADO", AMARILLO))
            elif resultado:
                color = AMARILLO if resultado.startswith(("TARDANZA", "YA")) else VERDE
                if resultado.startswith("RECHAZADO"):
                    color = ROJO
                detecciones.append((x, y, w, h, codigo + " " + resultado, color))
            else:
                detecciones.append((x, y, w, h, codigo + " ENVIANDO...", AMARILLO))

        if detecciones:
            with self.estado.lock:
                self.estado.detecciones = detecciones
                self.estado.detecciones_t = time.monotonic()

    def registrar(self, codigo):
        """Decide qué hacer con un QR visto en este cuadro.

        - "mismo":    la credencial sigue frente a la cámara (misma pasada): no se hace nada.
        - "nuevo":    primera pasada del día: se pone en la cola para registrar la asistencia.
        - "repetido": volvió a pasar (se retiró unos segundos y apareció de nuevo). Con conexión
                      se consulta a Laravel, que no duplica y avisa "ya registrado" en el panel.
        """
        ahora = time.monotonic()
        with self.estado.lock:
            self.estado.nuevo_dia_si_corresponde()
            visto_antes = self.visto.get(codigo)
            self.visto[codigo] = ahora
            if visto_antes is not None and ahora - visto_antes < self.pausa:
                return "mismo"

            repetido = codigo in self.estado.enviados_hoy
            sin_conexion = self.captura.enviador.conectado is False
            self.estado.enviados_hoy.add(codigo)
            self.estado.ultimas.appendleft({
                "hora": datetime.now().strftime("%H:%M:%S"),
                "codigo": codigo,
                "nombre": self.estado.nombres.get(codigo, ""),
                "estado": "YA REGISTRADO" if (repetido and not sin_conexion) else "EN COLA",
            })

        if repetido and sin_conexion:
            # Ya está guardado en la Pi: no se acumulan copias mientras no hay red
            return "repetido"

        self.cola.agregar(codigo)
        log.info("QR %s: %s", "repetido" if repetido else "leído", codigo)
        return "repetido" if repetido else "nuevo"


PAGINA = """<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SICCAM-QR - Cámara</title>
<style>
  body { margin: 0; background: #111; color: #eee; font-family: sans-serif; }
  header { padding: 10px 16px; background: #1b5e20; font-weight: bold; }
  main { display: flex; flex-wrap: wrap; gap: 16px; padding: 16px; }
  .video { flex: 2 1 480px; }
  .video img { width: 100%; border: 2px solid #4caf50; border-radius: 6px; }
  aside { flex: 1 1 260px; }
  .dato { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #333; }
  .ok { color: #66bb6a; } .mal { color: #ef5350; }
  table { width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 12px; }
  td { padding: 4px; border-bottom: 1px solid #333; }
</style>
</head>
<body>
<header>SICCAM-QR · Lector de asistencia
  <a href="/pantalla" style="float:right; color:#fff;">⛶ Pantalla completa</a></header>
<main>
  <div class="video"><img src="/video" alt="Cámara en vivo"></div>
  <aside>
    <div class="dato"><span>Cámara</span><span id="camara">…</span></div>
    <div class="dato"><span>Servidor Laravel</span><span id="servidor">…</span></div>
    <div class="dato"><span>Pendientes (offline)</span><span id="pendientes">…</span></div>
    <div class="dato"><span>FPS cámara</span><span id="fps">…</span></div>
    <div class="dato"><span>Búsquedas de QR por segundo</span><span id="fps_lectura">…</span></div>
    <div class="dato"><span>Reloj</span><span id="reloj">…</span></div>
    <table><tbody id="ultimas"></tbody></table>
  </aside>
</main>
<script>
function poner(id, texto, ok) {
  var el = document.getElementById(id);
  el.textContent = texto;
  el.className = ok === undefined ? '' : (ok ? 'ok' : 'mal');
}
function actualizar() {
  fetch('/estado').then(function (r) { return r.json(); }).then(function (e) {
    poner('camara', e.camara_ok ? 'conectada' : 'desconectada', e.camara_ok);
    poner('servidor', e.servidor_conectado === null ? 'sin intentos' : (e.servidor_conectado ? 'conectado' : 'sin conexión'), e.servidor_conectado !== false);
    poner('pendientes', e.pendientes, e.pendientes === 0);
    poner('fps', e.fps);
    poner('fps_lectura', e.fps_lectura);
    poner('reloj', e.reloj_sincronizado === null ? 'desconocido' : (e.reloj_sincronizado ? 'sincronizado' : 'SIN SINCRONIZAR'), e.reloj_sincronizado !== false);
    var tbody = document.getElementById('ultimas');
    tbody.innerHTML = '';
    e.ultimas.forEach(function (l) {
      var tr = document.createElement('tr');
      [l.hora, l.codigo, l.nombre, l.estado].forEach(function (v) {
        var td = document.createElement('td'); td.textContent = v; tr.appendChild(td);
      });
      tbody.appendChild(tr);
    });
  }).catch(function () {});
}
actualizar();
setInterval(actualizar, 2000);
</script>
</body>
</html>"""


# Vista para proyectar en la defensa: video a pantalla completa + cartel con la última lectura
PANTALLA = """<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SICCAM-QR - Pantalla</title>
<style>
  html, body { margin: 0; height: 100%; background: #000; overflow: hidden;
               font-family: 'Segoe UI', Roboto, Arial, sans-serif; color: #fff; }
  #video { position: fixed; inset: 0; width: 100vw; height: 100vh; object-fit: contain; background: #000; }

  /* Etiquetas pequeñas y translúcidas: el protagonista es el video */
  .pildora { position: fixed; padding: 6px 14px; border-radius: 999px; font-size: .85rem;
             background: rgba(0, 0, 0, .45); backdrop-filter: blur(4px); white-space: nowrap; }
  #marca { top: 12px; left: 12px; }
  #marca b { color: #8fe3a0; }
  #estado { top: 12px; right: 12px; }
  #reloj { font-weight: 700; margin-right: 8px; }
  .ok { color: #8fe3a0; } .mal { color: #ff9a8f; }
  #ayuda { bottom: 14px; left: 50%; transform: translateX(-50%); opacity: .85; transition: opacity .3s; }

  /* Aviso de lectura: compacto, aparece abajo y se va solo */
  #cartel { position: fixed; left: 50%; bottom: 14px; transform: translate(-50%, 12px);
            max-width: 70vw; padding: 8px 20px; border-radius: 12px; text-align: center;
            background: rgba(0, 0, 0, .6); opacity: 0; pointer-events: none;
            box-shadow: 0 4px 16px rgba(0, 0, 0, .4); transition: opacity .3s, transform .3s; }
  #cartel.visible { opacity: 1; transform: translate(-50%, 0); }
  #cartel .nombre { font-size: 1.3rem; font-weight: 700; }
  #cartel .detalle { font-size: .85rem; opacity: .95; }
  #cartel.presente { background: rgba(40, 167, 69, .9); }
  #cartel.tardanza { background: rgba(255, 193, 7, .92); color: #212529; }
  #cartel.registrado { background: rgba(26, 95, 42, .9); }
  #cartel.rechazado { background: rgba(220, 53, 69, .9); }

  #completa { position: fixed; right: 12px; bottom: 12px; padding: 5px 10px; border: 0; border-radius: 6px;
              background: rgba(255, 255, 255, .12); color: #fff; font-size: .8rem; cursor: pointer;
              opacity: .5; transition: opacity .2s; }
  #completa:hover { opacity: 1; }
  :fullscreen #completa { display: none; }
</style>
</head>
<body>
<img id="video" src="/video?limpio=1" alt="Cámara en vivo">

<div id="marca" class="pildora"><b>SICCAM-QR</b> · U.E. René Barrientos Ortuño "A"</div>
<div id="estado" class="pildora"><span id="reloj">--:--:--</span><span id="conexion">…</span></div>
<div id="ayuda" class="pildora">Acerque su credencial QR a la cámara</div>

<div id="cartel">
  <div class="nombre"></div>
  <div class="detalle"></div>
</div>

<button id="completa" title="También con la tecla F o doble clic en el video">⛶ Pantalla completa</button>

<script>
var cartel = document.getElementById('cartel');
var ayuda = document.getElementById('ayuda');
var ultimaClave = null;   // null = aún no se leyó el estado (no mostrar lecturas viejas al abrir)
var ocultarEn = 0;

function pantallaCompleta() {
  if (document.fullscreenElement) { document.exitFullscreen(); }
  else { document.documentElement.requestFullscreen().catch(function () {}); }
}
document.getElementById('completa').addEventListener('click', pantallaCompleta);
document.getElementById('video').addEventListener('dblclick', pantallaCompleta);
document.addEventListener('keydown', function (e) { if (e.key === 'f' || e.key === 'F') pantallaCompleta(); });

function reloj() {
  document.getElementById('reloj').textContent = new Date().toLocaleTimeString('es-BO', { hour12: false });
}
setInterval(reloj, 1000); reloj();

function mostrar(clase, nombre, detalle) {
  cartel.className = clase + ' visible';
  cartel.querySelector('.nombre').textContent = nombre;
  cartel.querySelector('.detalle').textContent = detalle;
  ayuda.style.opacity = 0;
  ocultarEn = Date.now() + 5000;
}

function ocultar() {
  cartel.classList.remove('visible');
  ayuda.style.opacity = '';
  ocultarEn = 0;
}

function actualizar() {
  fetch('/estado').then(function (r) { return r.json(); }).then(function (e) {
    var con = document.getElementById('conexion');
    if (e.servidor_conectado === false) {
      con.textContent = '● Sin conexión · ' + e.pendientes + ' guardado(s)';
      con.className = 'mal';
    } else if (!e.camara_ok) {
      con.textContent = '● Cámara desconectada';
      con.className = 'mal';
    } else {
      con.textContent = e.servidor_conectado ? '● En línea' : '● Listo';
      con.className = 'ok';
    }

    var l = e.ultimas[0];
    var clave = l ? (l.hora + l.codigo + l.estado + l.nombre) : '';
    if (ultimaClave === null) { ultimaClave = clave; return; }

    if (l && clave !== ultimaClave) {
      ultimaClave = clave;
      var estado = l.estado || '';
      var nombre = l.nombre || l.codigo;
      if (estado === 'EN COLA') {
        if (e.servidor_conectado === false) {
          mostrar('registrado', '✓ ' + nombre, 'Guardado en la Pi · se enviará al volver la conexión');
        } else {
          mostrar('', nombre, 'Registrando…');
        }
      } else if (estado.indexOf('PRESENTE') === 0) {
        mostrar('presente', '✓ ' + nombre, 'Presente · ' + l.hora);
      } else if (estado.indexOf('TARDANZA') === 0) {
        mostrar('tardanza', '✓ ' + nombre, 'Tardanza · ' + l.hora);
      } else if (estado.indexOf('YA') === 0) {
        mostrar('registrado', nombre, 'Ya registró su ingreso hoy');
      } else if (estado.indexOf('RECHAZADO') === 0) {
        mostrar('rechazado', '✗ ' + l.codigo, 'Código no registrado o estudiante inactivo');
      }
    }

    if (ocultarEn && Date.now() > ocultarEn) {
      ocultar();
    }
  }).catch(function () {
    var con = document.getElementById('conexion');
    con.textContent = '● El lector no responde';
    con.className = 'mal';
  });
}
setInterval(actualizar, 1000); actualizar();
</script>
</body>
</html>"""


def crear_app(estado, cola, enviador, detener):
    app = Flask(__name__)

    @app.route("/")
    def index():
        return PAGINA

    @app.route("/pantalla")
    def pantalla():
        return PANTALLA

    @app.route("/video")
    def video():
        # /video          -> con barra de estado (panel de Laravel)
        # /video?limpio=1 -> sin barra (vista /pantalla)
        limpio = request.args.get("limpio") == "1"
        contador = "clientes_limpio" if limpio else "clientes_video"
        id_attr = "frame_id_limpio" if limpio else "frame_id"
        jpeg_attr = "jpeg_limpio" if limpio else "jpeg"

        def generar():
            with estado.cond:
                setattr(estado, contador, getattr(estado, contador) + 1)
            ultimo = -1
            try:
                while not detener.is_set():
                    with estado.cond:
                        estado.cond.wait_for(lambda: getattr(estado, id_attr) != ultimo or detener.is_set(), timeout=2)
                        jpeg = getattr(estado, jpeg_attr)
                        if getattr(estado, id_attr) == ultimo or jpeg is None:
                            continue
                        ultimo = getattr(estado, id_attr)
                    yield b"--frame\r\nContent-Type: image/jpeg\r\n\r\n" + jpeg + b"\r\n"
            finally:
                with estado.cond:
                    setattr(estado, contador, getattr(estado, contador) - 1)

        return Response(generar(), mimetype="multipart/x-mixed-replace; boundary=frame")

    @app.route("/estado")
    def ver_estado():
        with estado.lock:
            ultimas = list(estado.ultimas)
        respuesta = jsonify({
            "camara_ok": estado.camara_ok,
            "fps": estado.fps_camara,
            "fps_lectura": estado.fps_lectura,
            "servidor_conectado": enviador.conectado,
            "ultimo_error": enviador.ultimo_error,
            "pendientes": cola.cantidad(),
            "reloj_sincronizado": estado.reloj_ok,
            "hora_pi": datetime.now().isoformat(timespec="seconds"),
            "ultimas": ultimas,
        })
        respuesta.headers["Access-Control-Allow-Origin"] = "*"
        return respuesta

    return app


def main():
    logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(name)s: %(message)s")
    # Sin esto, cada consulta a /estado (cada 2 s) llenaría el log del servicio
    logging.getLogger("werkzeug").setLevel(logging.WARNING)
    cfg = cargar_config()

    detener = threading.Event()
    estado = Estado()
    cola = ColaAsistencias(BASE / cfg.get("offline", "archivo_cola", fallback="cola_asistencias.db"))

    def al_confirmar(codigo, datos):
        estudiante = datos.get("estudiante", {})
        if datos.get("duplicado"):
            texto = "YA REGISTRADO"
        else:
            texto = str(estudiante.get("estado", "OK")).upper()
        estado.actualizar_lectura(codigo, texto, estudiante.get("nombre"))

    def al_descartar(codigo, motivo):
        # En la siguiente pasada se vuelve a intentar (por si el admin corrigió al estudiante)
        with estado.lock:
            estado.enviados_hoy.discard(codigo)
        estado.actualizar_lectura(codigo, "RECHAZADO")

    enviador = EnviadorLaravel(
        cola,
        url=cfg.get("laravel", "url"),
        token=cfg.get("laravel", "token"),
        detener=detener,
        timeout=cfg.getfloat("laravel", "timeout_seg", fallback=4),
        reintento_seg=cfg.getfloat("offline", "reintento_seg", fallback=15),
        al_confirmar=al_confirmar,
        al_descartar=al_descartar,
    )
    captura = Captura(cfg, estado, cola, enviador, detener)
    lector = LectorQR(cfg, estado, cola, captura, detener)

    def apagar(*_):
        log.info("Deteniendo SICCAM-QR...")
        detener.set()

    signal.signal(signal.SIGINT, apagar)
    signal.signal(signal.SIGTERM, apagar)

    pendientes = cola.cantidad()
    if pendientes:
        log.info("Hay %d lectura(s) pendiente(s) de una sesión anterior; se enviarán ahora", pendientes)

    enviador.start()
    captura.start()
    lector.start()

    servidor = None
    if cfg.getboolean("stream", "activo", fallback=True):
        puerto = cfg.getint("stream", "puerto", fallback=8080)
        servidor = make_server("0.0.0.0", puerto, crear_app(estado, cola, enviador, detener), threaded=True)
        threading.Thread(target=servidor.serve_forever, name="web", daemon=True).start()
        log.info("Vista en vivo: http://<IP-de-la-Pi>:%d", puerto)
    else:
        log.info("Stream desactivado: solo lectura de QR")

    try:
        while not detener.wait(1):
            pass
    finally:
        if servidor is not None:
            servidor.shutdown()
        captura.join(timeout=5)
        lector.join(timeout=5)
        log.info("SICCAM-QR detenido")


if __name__ == "__main__":
    main()
