#!/usr/bin/env bash
# Instala SICCAM-QR como servicio: arranca solo al encender la Pi (sin SSH)
# y se reinicia si se cae.
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

if [ ! -x "$PYTHON" ]; then
    echo "No existe $PYTHON"
    echo "Cree el entorno:  python3 -m venv camara_env && camara_env/bin/pip install -r requirements.txt"
    exit 1
fi
if [ ! -f "$DIR/config.ini" ]; then
    echo "Falta config.ini:  cp config.ini.ejemplo config.ini  (y edítelo)"
    exit 1
fi

# Permiso para usar la cámara
usermod -aG video "$USUARIO"

cat > "$SERVICIO" <<UNIT
[Unit]
Description=SICCAM-QR lector de camara QR
After=network-online.target time-sync.target
Wants=network-online.target

[Service]
Type=simple
User=$USUARIO
WorkingDirectory=$DIR
ExecStart=$PYTHON $DIR/camara_web.py
Restart=always
RestartSec=5
Environment=PYTHONUNBUFFERED=1

[Install]
WantedBy=multi-user.target
UNIT

systemctl daemon-reload
systemctl enable siccam-camara.service
systemctl restart siccam-camara.service
sleep 3
systemctl --no-pager status siccam-camara.service | head -n 12 || true

echo
echo "Listo. Comandos útiles:"
echo "  journalctl -u siccam-camara -f        # ver lo que hace en vivo"
echo "  sudo systemctl restart siccam-camara  # reiniciar"
echo "  sudo systemctl stop siccam-camara     # detener (p. ej. para probar a mano)"
