@echo off
rem Descarga las librerias del panel a public\vendor para que funcione SIN internet.
rem Ejecutar UNA sola vez (con internet) desde la carpeta del proyecto.
cd /d "%~dp0"
set V=public\vendor

mkdir %V%\jquery %V%\bootstrap %V%\adminlte %V%\datatables %V%\toastify 2>nul
mkdir %V%\fontawesome\css %V%\fontawesome\webfonts %V%\bootstrap-icons\fonts 2>nul

call :bajar https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js %V%\jquery\jquery.min.js
call :bajar https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js %V%\bootstrap\bootstrap.bundle.min.js
call :bajar https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css %V%\adminlte\adminlte.min.css
call :bajar https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js %V%\adminlte\adminlte.min.js
call :bajar https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css %V%\datatables\dataTables.bootstrap4.min.css
call :bajar https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js %V%\datatables\jquery.dataTables.min.js
call :bajar https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js %V%\datatables\dataTables.bootstrap4.min.js
call :bajar https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.css %V%\toastify\toastify.min.css
call :bajar https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.js %V%\toastify\toastify.min.js

call :bajar https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css %V%\fontawesome\css\all.min.css
for %%F in (fa-solid-900 fa-regular-400 fa-brands-400 fa-v4compatibility) do (
    call :bajar https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/webfonts/%%F.woff2 %V%\fontawesome\webfonts\%%F.woff2
    call :bajar https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/webfonts/%%F.ttf %V%\fontawesome\webfonts\%%F.ttf
)

call :bajar https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css %V%\bootstrap-icons\bootstrap-icons.min.css
call :bajar https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff2 %V%\bootstrap-icons\fonts\bootstrap-icons.woff2
call :bajar https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff %V%\bootstrap-icons\fonts\bootstrap-icons.woff

echo.
echo Listo. Librerias guardadas en %V%
pause
exit /b

:bajar
curl -fsSL -o "%~2" "%~1"
if errorlevel 1 (echo   ERROR  %~1) else (echo   OK     %~2)
exit /b