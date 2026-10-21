#!/usr/bin/env bash
# Instala SICCAM-QR como servicio: arranca solo al encender la Pi (sin SSH)
# y se reinicia si se cae. También instala la toma de hora desde la laptop
# (la Pi no tiene reloj con pila: sin esto, sin internet arranca con la hora mal).
#
# Uso (dentro de esta carpeta):   sudo bash instalar_servicio.sh
# Si el entorno virtual está en otra ruta:
#   sudo PYTHON=/home/USUARIO/camara_env/bin/python bash instalar_servicio.sh
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
    echo "Ejecute con sudo: sudo bash instalar_servicio.sh"
    exit 1
fi

USUARIO="${SUDO_USER:-$(logname)}"
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PYTHON="${PYTHON:-$DIR/camara_env/bin/python}"
SERVICIO=/etc/systemd/system/siccam-camara.service
HORA_SERVICIO=/etc/systemd/system/siccam-hora.service
HORA_TEMPORIZADOR=/etc/systemd/system/siccam-hora.timer

if [ ! -x "$PYTHON" ]; then
    echo "No existe $PYTHON"
    echo "Cree el entorno:  python3 -m venv camara_env && camara_env/bin/pip install -r requirements.txt"
    exit 1
fi
if [ ! -f "$DIR/config.ini" ]; then
    echo "Falta config.ini:  cp config.ini.ejemplo config.ini  (y edítelo)"
    exit 1
fi
if [ ! -f "$DIR/sincronizar_hora.sh" ]; then
    echo "Falta sincronizar_hora.sh en $DIR"
    exit 1
fi

# Si se copió desde Windows con finales CRLF, bash no lo ejecuta
sed -i 's/\r$//' "$DIR/sincronizar_hora.sh"

# Permiso para usar la cámara
usermod -aG video "$USUARIO"

# Lector: antes de abrir la cámara toma la hora de la laptop (espera hasta 30 s).
# El "+" ejecuta ese paso como root (cambiar la hora lo requiere); el lector corre como usuario.
cat > "$SERVICIO" <<UNIT
[Unit]
Description=SICCAM-QR lector de camara QR
After=network-online.target time-sync.target
Wants=network-online.target

[Service]
Type=simple
User=$USUARIO
WorkingDirectory=$DIR
ExecStartPre=+/bin/bash $DIR/sincronizar_hora.sh --esperar 30
ExecStart=$PYTHON $DIR/camara_web.py
TimeoutStartSec=90
Restart=always
RestartSec=5
Environment=PYTHONUNBUFFERED=1

[Install]
WantedBy=multi-user.target
UNIT

# Revisión de la hora cada minuto (por si la Pi se encendió antes que la laptop)
cat > "$HORA_SERVICIO" <<UNIT
[Unit]
Description=SICCAM-QR tomar la hora de la laptop

[Service]
Type=oneshot
ExecStart=/bin/bash $DIR/sincronizar_hora.sh
UNIT

cat > "$HORA_TEMPORIZADOR" <<UNIT
[Unit]
Description=SICCAM-QR revisar la hora cada minuto

[Timer]
OnBootSec=45s
OnUnitActiveSec=60s
AccuracySec=5s

[Install]
WantedBy=timers.target
UNIT

systemctl daemon-reload
systemctl enable --now siccam-hora.timer
systemctl enable siccam-camara.service
systemctl restart siccam-camara.service
sleep 3
systemctl --no-pager status siccam-camara.service | head -n 12 || true

echo
echo "Listo. Comandos útiles:"
echo "  journalctl -u siccam-camara -f        # ver lo que hace en vivo"
echo "  journalctl -u siccam-hora             # ver las correcciones de hora"
echo "  sudo systemctl restart siccam-camara  # reiniciar"
echo "  sudo systemctl stop siccam-camara     # detener (p. ej. para probar a mano)"
