#!/usr/bin/env python3
"""SICCAM-QR - Lector de códigos QR (Raspberry Pi 4 + cámara USB).

- Un hilo lee la cámara y decodifica QR TODO el tiempo, haya o no alguien viendo el video.
- Lee varios QR a la vez; cada estudiante se envía una sola vez por día.
- Cada lectura se guarda en una cola SQLite y otro hilo la envía a Laravel
  (si no hay red, se guarda y se sincroniza después con la hora real de lectura).
- http://IP_DE_LA_PI:8080/        vista en vivo + estado (para el jurado)
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
from flask import Flask, Response, jsonify
from pyzbar import pyzbar
from pyzbar.pyzbar import ZBarSymbol
from werkzeug.serving import make_server

from cola_asistencias import ColaAsistencias, EnviadorLaravel

BASE = Path(__file__).resolve().parent
log = logging.getLogger("siccam.camara")

VERDE = (0, 200, 0)
AMARILLO = (0, 200, 255)
ROJO = (0, 0, 255)
BLANCO = (255, 255, 255)


def cargar_config():
    ruta = BASE / "config.ini"
    if not ruta.exists():
        sys.exit("Falta config.ini: copie config.ini.ejemplo como config.ini y edítelo.")
    cfg = configparser.ConfigParser(interpolation=None)
    cfg.read(ruta, encoding="utf-8")
    return cfg


def reloj_sincronizado():
    """True/False según NTP; None si no se puede saber (p. ej. fuera de Linux)."""
    try:
        salida = subprocess.run(
            ["timedatectl", "show", "-p", "NTPSynchronized", "--value"],
            capture_output=True, text=True, timeout=3,
        ).stdout.strip()
        return salida == "yes" if salida else None
    except Exception:
        return None


class Estado:
    """Datos compartidos entre la cámara, el envío y el servidor web."""

    def __init__(self):
        self.lock = threading.Lock()
        self.cond = threading.Condition()
        self.camara_ok = False
        self.fps = 0.0
        self.reloj_ok = None
        self.dia = date.today()
        self.enviados_hoy = set()      # códigos ya puestos en cola hoy
        self.resultado = {}            # código -> texto para mostrar (PRESENTE, TARDANZA...)
        self.ultimas = deque(maxlen=12)
        self.jpeg = None
        self.frame_id = 0
        self.clientes_video = 0

    def nuevo_dia_si_corresponde(self):
        hoy = date.today()
        if self.dia != hoy:
            self.dia = hoy
            self.enviados_hoy.clear()
            self.resultado.clear()

    def actualizar_lectura(self, codigo, texto, nombre=None):
        with self.lock:
            self.resultado[codigo] = texto
            for lectura in self.ultimas:
                if lectura["codigo"] == codigo:
                    lectura["estado"] = texto
                    if nombre:
                        lectura["nombre"] = nombre
                    break

    def publicar_jpeg(self, jpeg):
        with self.cond:
            self.jpeg = jpeg
            self.frame_id += 1
            self.cond.notify_all()


class LectorQR(threading.Thread):
    def __init__(self, cfg, estado, cola, enviador, detener):
        super().__init__(name="lector", daemon=True)
        self.estado = estado
        self.cola = cola
        self.enviador = enviador
        self.detener = detener

        self.indice = cfg.getint("camara", "indice", fallback=0)
        self.ancho = cfg.getint("camara", "ancho", fallback=1280)
        self.alto = cfg.getint("camara", "alto", fallback=720)
        self.fps_camara = cfg.getint("camara", "fps", fallback=30)
        self.formato = cfg.get("camara", "formato", fallback="MJPG").strip()

        self.cooldown = cfg.getfloat("lectura", "cooldown_seg", fallback=10)
        self.patron = re.compile(cfg.get("lectura", "patron_codigo", fallback=r"^EST-\d{4}-\d{4}$"))

        self.stream_activo = cfg.getboolean("stream", "activo", fallback=True)
        self.intervalo_stream = 1.0 / max(1, cfg.getint("stream", "fps_max", fallback=12))
        self.calidad_jpeg = cfg.getint("stream", "calidad_jpeg", fallback=70)

        self.cap = None
        self.ultimo_visto = {}  # código -> instante de la última vez que se encoló
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
        cap.set(cv2.CAP_PROP_FPS, self.fps_camara)
        cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)  # siempre el cuadro más reciente

        log.info("Cámara %d abierta: %dx%d a %.0f fps", self.indice,
                 cap.get(cv2.CAP_PROP_FRAME_WIDTH), cap.get(cv2.CAP_PROP_FRAME_HEIGHT),
                 cap.get(cv2.CAP_PROP_FPS))
        return cap

    def cerrar_camara(self):
        if self.cap is not None:
            self.cap.release()
            self.cap = None

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
            self.procesar(frame)

            cuadros += 1
            transcurrido = time.monotonic() - inicio_fps
            if transcurrido >= 1:
                self.estado.fps = round(cuadros / transcurrido, 1)
                cuadros = 0
                inicio_fps = time.monotonic()

            self.publicar_stream(frame)

        self.cerrar_camara()

    # ---------- lectura de QR ----------
    def procesar(self, frame):
        gris = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
        for qr in pyzbar.decode(gris, symbols=[ZBarSymbol.QRCODE]):
            texto = qr.data.decode("utf-8", errors="replace")
            codigo = texto.split("|")[0].strip()
            x, y, w, h = qr.rect

            if not self.patron.fullmatch(codigo):
                self.dibujar(frame, x, y, w, h, "QR NO VALIDO", ROJO)
                continue

            situacion = self.registrar(codigo)
            with self.estado.lock:
                resultado = self.estado.resultado.get(codigo, "")

            if situacion == "nuevo":
                self.dibujar(frame, x, y, w, h, codigo + " LEIDO", VERDE)
            elif resultado:
                color = AMARILLO if resultado.startswith(("TARDANZA", "YA")) else VERDE
                if resultado.startswith("RECHAZADO"):
                    color = ROJO
                self.dibujar(frame, x, y, w, h, codigo + " " + resultado, color)
            else:
                self.dibujar(frame, x, y, w, h, codigo + " ENVIANDO...", AMARILLO)

    def registrar(self, codigo):
        """Pone el código en la cola una sola vez por día."""
        ahora = time.monotonic()
        with self.estado.lock:
            self.estado.nuevo_dia_si_corresponde()
            if codigo in self.estado.enviados_hoy:
                return "ya_en_cola"
            ultimo = self.ultimo_visto.get(codigo)
            if ultimo is not None and ahora - ultimo < self.cooldown:
                return "espera"
            self.ultimo_visto[codigo] = ahora
            self.estado.enviados_hoy.add(codigo)
            self.estado.ultimas.appendleft({
                "hora": datetime.now().strftime("%H:%M:%S"),
                "codigo": codigo,
                "nombre": "",
                "estado": "EN COLA",
            })

        self.cola.agregar(codigo)
        log.info("QR leído: %s", codigo)
        return "nuevo"

    # ---------- video ----------
    def dibujar(self, frame, x, y, w, h, texto, color):
        cv2.rectangle(frame, (x, y), (x + w, y + h), color, 3)
        cv2.putText(frame, texto, (x, max(25, y - 10)), cv2.FONT_HERSHEY_SIMPLEX, 0.7, color, 2)

    def barra_estado(self, frame):
        conectado = self.enviador.conectado
        servidor = "SERVIDOR OK" if conectado else ("SIN CONEXION" if conectado is False else "SERVIDOR ?")
        texto = "%s | PENDIENTES %d | %.0f FPS | %s" % (
            servidor, self.cola.cantidad(), self.estado.fps, datetime.now().strftime("%H:%M:%S"))
        if self.estado.reloj_ok is False:
            texto += " | RELOJ SIN SINCRONIZAR"
        cv2.rectangle(frame, (0, 0), (frame.shape[1], 32), (0, 0, 0), -1)
        cv2.putText(frame, texto, (10, 22), cv2.FONT_HERSHEY_SIMPLEX, 0.6,
                    VERDE if conectado else AMARILLO, 2)

    def publicar_stream(self, frame):
        # Solo se codifica JPEG si alguien está viendo, y como máximo fps_max por segundo
        if not self.stream_activo or self.estado.clientes_video == 0:
            return
        ahora = time.monotonic()
        if ahora - self._ultimo_jpeg < self.intervalo_stream:
            return
        self._ultimo_jpeg = ahora

        self.barra_estado(frame)
        ok, buffer = cv2.imencode(".jpg", frame, [cv2.IMWRITE_JPEG_QUALITY, self.calidad_jpeg])
        if ok:
            self.estado.publicar_jpeg(buffer.tobytes())

    def publicar_aviso(self, texto):
        if not self.stream_activo or self.estado.clientes_video == 0:
            return
        imagen = np.zeros((self.alto // 2, self.ancho // 2, 3), dtype=np.uint8)
        cv2.putText(imagen, texto, (30, imagen.shape[0] // 2), cv2.FONT_HERSHEY_SIMPLEX, 1.0, ROJO, 2)
        ok, buffer = cv2.imencode(".jpg", imagen)
        if ok:
            self.estado.publicar_jpeg(buffer.tobytes())

    def revisar_reloj(self):
        ahora = time.monotonic()
        if ahora - self._ultimo_reloj >= 300 or self._ultimo_reloj == 0:
            self._ultimo_reloj = ahora
            self.estado.reloj_ok = reloj_sincronizado()
            if self.estado.reloj_ok is False:
                log.warning("El reloj de la Pi NO está sincronizado (NTP): las horas pueden ser incorrectas")


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
<header>SICCAM-QR · Lector de asistencia</header>
<main>
  <div class="video"><img src="/video" alt="Cámara en vivo"></div>
  <aside>
    <div class="dato"><span>Cámara</span><span id="camara">…</span></div>
    <div class="dato"><span>Servidor Laravel</span><span id="servidor">…</span></div>
    <div class="dato"><span>Pendientes (offline)</span><span id="pendientes">…</span></div>
    <div class="dato"><span>FPS</span><span id="fps">…</span></div>
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


def crear_app(estado, cola, enviador, detener):
    app = Flask(__name__)

    @app.route("/")
    def index():
        return PAGINA

    @app.route("/video")
    def video():
        def generar():
            with estado.cond:
                estado.clientes_video += 1
            ultimo = -1
            try:
                while not detener.is_set():
                    with estado.cond:
                        estado.cond.wait_for(lambda: estado.frame_id != ultimo or detener.is_set(), timeout=2)
                        if estado.frame_id == ultimo or estado.jpeg is None:
                            continue
                        ultimo = estado.frame_id
                        jpeg = estado.jpeg
                    yield b"--frame\r\nContent-Type: image/jpeg\r\n\r\n" + jpeg + b"\r\n"
            finally:
                with estado.cond:
                    estado.clientes_video -= 1

        return Response(generar(), mimetype="multipart/x-mixed-replace; boundary=frame")

    @app.route("/estado")
    def ver_estado():
        with estado.lock:
            ultimas = list(estado.ultimas)
        respuesta = jsonify({
            "camara_ok": estado.camara_ok,
            "fps": estado.fps,
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
        # Permite volver a intentar si el admin corrige el estudiante (pasado el cooldown)
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
    lector = LectorQR(cfg, estado, cola, enviador, detener)

    def apagar(*_):
        log.info("Deteniendo SICCAM-QR...")
        detener.set()

    signal.signal(signal.SIGINT, apagar)
    signal.signal(signal.SIGTERM, apagar)

    pendientes = cola.cantidad()
    if pendientes:
        log.info("Hay %d lectura(s) pendiente(s) de una sesión anterior; se enviarán ahora", pendientes)

    enviador.start()
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
        lector.join(timeout=5)
        log.info("SICCAM-QR detenido")


if __name__ == "__main__":
    main()
