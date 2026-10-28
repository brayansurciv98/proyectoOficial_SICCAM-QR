@echo off
rem Cierra las ventanas abiertas por iniciar_siccam.bat (MySQL queda encendido).
rem Las busca por el comando que ejecutan (php artisan ...), no por el titulo de la ventana:
rem en Windows Terminal el titulo no sirve para encontrarlas.
powershell -NoProfile -ExecutionPolicy Bypass -Command "$p = @(Get-CimInstance Win32_Process -Filter \"Name='cmd.exe' OR Name='php.exe'\" | Where-Object { $_.CommandLine -match 'artisan (serve|reverb:start|queue:work|schedule:work)' }); foreach ($x in $p) { taskkill /F /T /PID $x.ProcessId 2>$null | Out-Null }; if ($p.Count) { Write-Host 'Servicios de SICCAM-QR detenidos.' } else { Write-Host 'No habia servicios de SICCAM-QR en ejecucion.' }"
echo MySQL sigue encendido: apaguelo desde el panel de XAMPP si quiere.
pause
