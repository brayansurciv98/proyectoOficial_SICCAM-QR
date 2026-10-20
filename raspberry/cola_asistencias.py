"""Cola persistente de lecturas QR (SQLite) y envío a Laravel en segundo plano.

Cada lectura se guarda primero en disco y después un hilo aparte la envía.
Si Laravel no responde, la lectura sigue guardada (aunque se corte la luz)
y se reintenta sola, enviando la hora REAL en que se leyó el QR.
"""

import logging
import sqlite3
import threading
from datetime import datetime

import requests

log = logging.getLogger("siccam.cola")


class ColaAsistencias:
    """Lecturas pendientes de enviar, en orden de llegada."""

    def __init__(self, ruta_db):
        self._lock = threading.Lock()
        self._hay_nuevos = threading.Event()
        # isolation_level=None: cada INSERT/DELETE se guarda en disco al instante
        self._con = sqlite3.connect(str(ruta_db), check_same_thread=False, isolation_level=None)
        self._con.execute("PRAGMA journal_mode=WAL")
        self._con.execute("PRAGMA synchronous=FULL")
        self._con.execute(
            """CREATE TABLE IF NOT EXISTS pendientes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                codigo TEXT NOT NULL,
                fecha_hora TEXT NOT NULL,
                intentos INTEGER NOT NULL DEFAULT 0,
                ultimo_error TEXT
            )"""
        )
        # Lo que Laravel rechazó (404 / 422) queda aquí para revisarlo a mano
        self._con.execute(
            """CREATE TABLE IF NOT EXISTS descartados (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                codigo TEXT NOT NULL,
                fecha_hora TEXT NOT NULL,
                motivo TEXT,
                descartado_en TEXT NOT NULL
            )"""
        )

    def agregar(self, codigo, momento=None):
        momento = momento or datetime.now()
        with self._lock:
            self._con.execute(
                "INSERT INTO pendientes (codigo, fecha_hora) VALUES (?, ?)",
                (codigo, momento.isoformat(timespec="seconds")),
            )
        self._hay_nuevos.set()

    def siguientes(self, limite=20):
        with self._lock:
            return self._con.execute(
                "SELECT id, codigo, fecha_hora FROM pendientes ORDER BY id LIMIT ?", (limite,)
            ).fetchall()

    def eliminar(self, id_):
        with self._lock:
            self._con.execute("DELETE FROM pendientes WHERE id = ?", (id_,))

    def descartar(self, id_, codigo, fecha_hora, motivo):
        with self._lock:
            self._con.execute(
                "INSERT INTO descartados (codigo, fecha_hora, motivo, descartado_en) VALUES (?, ?, ?, ?)",
                (codigo, fecha_hora, motivo, datetime.now().isoformat(timespec="seconds")),
            )
            self._con.execute("DELETE FROM pendientes WHERE id = ?", (id_,))

    def marcar_error(self, id_, error):
        with self._lock:
            self._con.execute(
                "UPDATE pendientes SET intentos = intentos + 1, ultimo_error = ? WHERE id = ?",
                (error, id_),
            )

    def cantidad(self):
        with self._lock:
            return self._con.execute("SELECT COUNT(*) FROM pendientes").fetchone()[0]

    def esperar_nuevos(self, segundos):
        self._hay_nuevos.wait(segundos)
        self._hay_nuevos.clear()


class EnviadorLaravel(threading.Thread):
    """Envía la cola a POST /api/asistencias/marcar, en orden y sin bloquear la cámara."""

    def __init__(self, cola, url, token, detener, timeout=4, reintento_seg=15,
                 al_confirmar=None, al_descartar=None):
        super().__init__(name="enviador", daemon=True)
        self.cola = cola
        self.url = url
        self.detener = detener
        self.timeout = timeout
        self.reintento_seg = reintento_seg
        self.al_confirmar = al_confirmar or (lambda codigo, datos: None)
        self.al_descartar = al_descartar or (lambda codigo, motivo: None)
        self.conectado = None  # None = todavía no se intentó
        self.ultimo_error = ""

        self.sesion = requests.Session()
        self.sesion.headers.update({
            "X-Raspberry-Token": token,
            "Accept": "application/json",
            "Content-Type": "application/json",
        })

    def run(self):
        while not self.detener.is_set():
            pendientes = self.cola.siguientes()
            if not pendientes:
                self.cola.esperar_nuevos(self.reintento_seg)
                continue

            fallo = False
            for id_, codigo, fecha_hora in pendientes:
                if self.detener.is_set():
                    return
                resultado, detalle = self._enviar(codigo, fecha_hora)

                if resultado == "ok":
                    self.cola.eliminar(id_)
                    self.al_confirmar(codigo, detalle)
                elif resultado == "descartar":
                    log.warning("Descartado %s (%s): %s", codigo, fecha_hora, detalle)
                    self.cola.descartar(id_, codigo, fecha_hora, detalle)
                    self.al_descartar(codigo, detalle)
                else:
                    # Sin red o error del servidor: se conserva y se respeta el orden
                    self.cola.marcar_error(id_, detalle)
                    fallo = True
                    break

            if fallo:
                pendientes = self.cola.cantidad()
                log.warning("No se pudo enviar (%s). Pendientes en cola: %d. Reintento en %ds",
                            self.ultimo_error, pendientes, self.reintento_seg)
                self.detener.wait(self.reintento_seg)

    def _enviar(self, codigo, fecha_hora):
        try:
            r = self.sesion.post(
                self.url,
                json={"codigo_estudiante": codigo, "fecha_hora": fecha_hora},
                timeout=self.timeout,
            )
        except requests.RequestException as e:
            self.conectado = False
            self.ultimo_error = "sin conexión con Laravel (" + e.__class__.__name__ + ")"
            return "reintentar", self.ultimo_error

        self.conectado = True
        try:
            datos = r.json()
        except ValueError:
            datos = {}

        if r.status_code in (200, 201):
            self.ultimo_error = ""
            estudiante = datos.get("estudiante", {})
            log.info("Enviado %s -> HTTP %d %s %s", codigo, r.status_code,
                     estudiante.get("nombre", ""),
                     "(ya registrado)" if datos.get("duplicado") else estudiante.get("estado", ""))
            return "ok", datos

        if r.status_code in (404, 422):
            # Código que no existe/inactivo o fecha_hora fuera de rango: reintentar no sirve
            return "descartar", "HTTP %d: %s" % (r.status_code, datos.get("message", r.text[:200]))

        if r.status_code == 401:
            self.ultimo_error = "token rechazado: revise [laravel] token en config.ini"
            log.error(self.ultimo_error)
            return "reintentar", self.ultimo_error

        # 5xx, 419, 429...: problema del servidor, se reintenta
        self.ultimo_error = "HTTP %d del servidor" % r.status_code
        return "reintentar", self.ultimo_error
