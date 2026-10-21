#!/usr/bin/env bash
# Pone en la Raspberry la hora de la laptop (Laravel), sin depender de internet.
# Laravel envía su hora en cada respuesta (cabecera "Date"); se toma de /up.
#
# Uso (lo ejecuta systemd como root):
#   sincronizar_hora.sh                 -> una revisión (la usa el temporizador cada minuto)
#   sincronizar_hora.sh --esperar 30    -> al encender: espera hasta 30 s a que la laptop responda
# Para probar sin cambiar la hora: SICCAM_SIMULAR=1 sincronizar_hora.sh

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# Señal para camara_web.py de que la hora ya se tomó de la laptop (/run se borra al apagar)
MARCA=/run/siccam-hora-ok
ESPERAR=0
if [ "${1:-}" = "--esperar" ]; then
    ESPERAR="${2:-30}"
fi

# Dirección de Laravel tomada de config.ini (url = http://IP:8000/api/...)
URL=$(grep -E '^[[:space:]]*url[[:space:]]*=' "$DIR/config.ini" 2>/dev/null | head -n 1 | cut -d= -f2- | tr -d ' \r')
BASE=$(echo "$URL" | sed -E 's|^(https?://[^/]+).*|\1|')
if [ -z "$BASE" ]; then
    echo "No se encontró la url de Laravel en $DIR/config.ini"
    exit 0
fi

fin=$(( $(date +%s) + ESPERAR ))
while true; do
    FECHA=$(curl -s -I --max-time 3 "$BASE/up" | tr -d '\r' | grep -i '^date:' | cut -d' ' -f2-)
    SERVIDOR=$( [ -n "$FECHA" ] && date -d "$FECHA" +%s 2>/dev/null )

    if [ -n "$SERVIDOR" ]; then
        DIFERENCIA=$(( SERVIDOR - $(date +%s) ))
        ABSOLUTA=${DIFERENCIA#-}
        if [ "$ABSOLUTA" -gt 2 ]; then
            if [ -n "${SICCAM_SIMULAR:-}" ]; then
                echo "SIMULACION: se corregiria la hora en $DIFERENCIA s -> $(date -d "@$SERVIDOR" '+%Y-%m-%d %H:%M:%S')"
            elif date -s "@$SERVIDOR" > /dev/null; then
                echo "Hora corregida con la de la laptop (diferencia: $DIFERENCIA s): $(date '+%Y-%m-%d %H:%M:%S')"
                touch "$MARCA"
            fi
        else
            if [ "$ESPERAR" -gt 0 ] || [ -n "${SICCAM_SIMULAR:-}" ]; then
                echo "La hora ya coincide con la de la laptop (diferencia: $DIFERENCIA s)"
            fi
            [ -z "${SICCAM_SIMULAR:-}" ] && touch "$MARCA"
        fi
        exit 0
    fi

    if [ "$(date +%s)" -ge "$fin" ]; then
        # Solo se avisa al encender; cada minuto sería repetir lo mismo en el registro
        if [ "$ESPERAR" -gt 0 ]; then
            echo "Laravel no responde en $BASE: la hora no se cambió (se reintenta cada minuto)"
        fi
        exit 0
    fi
    sleep 3
done
