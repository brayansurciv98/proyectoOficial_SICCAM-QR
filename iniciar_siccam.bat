@echo off
title SICCAM-QR - Inicio
cd /d "%~dp0"

echo ============================================
echo    SICCAM-QR - Iniciando servicios
echo ============================================

rem 1. MySQL de XAMPP (solo si no esta encendido)
tasklist /FI "IMAGENAME eq mysqld.exe" | find /I "mysqld.exe" >nul
if errorlevel 1 (
    echo Iniciando MySQL...
    start "XAMPP MySQL" /min C:\xampp\mysql_start.bat
    timeout /t 6 /nobreak >nul
) else (
    echo MySQL ya estaba encendido.
)

rem 2. Laravel visible en la red (celulares y Raspberry)
start "SICCAM - Laravel 8000" cmd /k php artisan serve --host=0.0.0.0 --port=8000

rem 3. Tiempo real del panel (Reverb)
start "SICCAM - Reverb 8080" cmd /k php artisan reverb:start

rem 4. Envio de notificaciones push
start "SICCAM - Notificaciones" cmd /k php artisan queue:work

rem 5. Cierre automatico de asistencia (a la hora de Configuracion)
start "SICCAM - Tareas programadas" cmd /k php artisan schedule:work

timeout /t 4 /nobreak >nul
start "" http://localhost:8000

echo.
echo Listo. IP de esta PC (para la app y la Raspberry):
ipconfig | findstr /C:"IPv4"
echo.
echo Para detener todo ejecute detener_siccam.bat
pause