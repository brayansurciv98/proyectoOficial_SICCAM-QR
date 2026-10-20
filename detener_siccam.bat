@echo off
rem Cierra las ventanas abiertas por iniciar_siccam.bat (MySQL queda encendido)
taskkill /FI "WINDOWTITLE eq SICCAM - *" /T /F >nul 2>&1
echo Servicios de SICCAM-QR detenidos.
echo MySQL sigue encendido: apaguelo desde el panel de XAMPP si quiere.
pause