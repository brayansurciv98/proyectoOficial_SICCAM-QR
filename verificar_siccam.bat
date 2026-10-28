@echo off
title SICCAM-QR - Verificacion
cd /d "%~dp0"
rem Revisa que todo este listo antes de una demostracion (ejecutar despues de iniciar_siccam.bat)
php artisan siccam:verificar
echo.
pause
