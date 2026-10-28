<?php

namespace App\Console\Commands;

use App\Models\Asistencia;
use App\Models\Estudiante;
use App\Models\Tutor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Revisión rápida antes de una demostración o de la prueba piloto:
 * servicios encendidos, red, internet, Firebase y la Raspberry Pi.
 */
class VerificarSistemaCommand extends Command
{
    protected $signature = 'siccam:verificar';

    protected $description = 'Revisa que todo SICCAM-QR esté listo (servicios, red, internet, Firebase y Raspberry Pi)';

    private int $fallas = 0;

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <options=bold>SICCAM-QR · Verificación antes de la demostración</>');
        $this->newLine();

        // 1. Base de datos
        try {
            DB::connection()->getPdo();
            $pendientes = collect(Artisan::call('migrate:status', ['--pending' => true]) === 0
                ? explode("\n", Artisan::output()) : [])
                ->filter(fn ($l) => str_contains($l, 'Pending'))->count();
            $this->fila('Base de datos (MySQL)', true, $pendientes ? "conectada, pero faltan {$pendientes} migración(es): php artisan migrate" : 'conectada');
            if ($pendientes) {
                $this->fallas++;
            }
        } catch (Throwable $e) {
            $this->fila('Base de datos (MySQL)', false, 'no conecta: encienda MySQL en XAMPP o ejecute iniciar_siccam.bat');
            $this->resumen();
            return self::FAILURE;
        }

        // 2. Servicios de iniciar_siccam.bat
        $this->fila('Panel web (puerto 8000)', $this->puerto('127.0.0.1', 8000), '', 'ejecute iniciar_siccam.bat (ventana "SICCAM - Laravel 8000")');
        $this->fila('Tiempo real (Reverb, puerto ' . config('reverb.servers.reverb.port', 8080) . ')',
            $this->puerto('127.0.0.1', (int) config('reverb.servers.reverb.port', 8080)), '', 'ventana "SICCAM - Reverb 8080"');

        $procesos = $this->procesosPhp();
        $this->fila('Envío de notificaciones (queue:work)', $procesos === null ? null : str_contains($procesos, 'queue:work'),
            '', 'ventana "SICCAM - Notificaciones"');
        $this->fila('Tareas programadas (schedule:work)', $procesos === null ? null : str_contains($procesos, 'schedule:work'),
            '', 'ventana "SICCAM - Tareas programadas" (marca las ausencias)');

        try {
            $atascados = DB::table('jobs')->where('available_at', '<', now()->subMinutes(2)->timestamp)->count();
        } catch (Throwable $e) {
            $atascados = 0;
        }
        if ($atascados) {
            $this->fila('Notificaciones en espera', false, "{$atascados} sin enviar desde hace más de 2 minutos: revise queue:work");
        }

        // 3. Red SICCAM-QR, internet y Firebase
        $ips = gethostbynamel(gethostname()) ?: [];
        $this->fila('Zona Wi-Fi SICCAM-QR (192.168.137.1)', in_array('192.168.137.1', $ips, true),
            '', 'encienda la zona con cobertura inalámbrica de Windows');
        $this->fila('Internet en la laptop', $this->puerto('fcm.googleapis.com', 443),
            '', 'sin internet no salen las notificaciones push (la app y el registro sí funcionan)');

        $credenciales = config('services.firebase.credenciales');
        $firebase = is_file($credenciales) ? json_decode(file_get_contents($credenciales), true) : null;
        $this->fila('Credenciales de Firebase', !empty($firebase['project_id']),
            !empty($firebase['project_id']) ? 'proyecto ' . $firebase['project_id'] : 'falta storage/app/private/firebase-credenciales.json');

        // 4. Raspberry Pi
        $video = config('services.raspberry.stream_url');
        if ($video) {
            $base = preg_replace('#^(https?://[^/]+).*#', '$1', $video);
            try {
                $estado = Http::timeout(3)->get($base . '/estado')->json();
                $this->fila('Raspberry Pi: lector', true, 'responde en ' . $base);
                $this->fila('Raspberry Pi: cámara', (bool) ($estado['camara_ok'] ?? false),
                    ($estado['camara_ok'] ?? false) ? ($estado['fps'] ?? '?') . ' fps' : 'la cámara no está abierta');
                $this->fila('Raspberry Pi: hora', (bool) ($estado['reloj_sincronizado'] ?? false),
                    ($estado['reloj_sincronizado'] ?? false) ? 'tomada de la laptop' : 'espere un minuto: se toma de la laptop');
                $pendientesPi = (int) ($estado['pendientes'] ?? 0);
                $this->fila('Raspberry Pi: lecturas sin enviar', $pendientesPi === 0,
                    $pendientesPi ? "{$pendientesPi} en cola (se envían solas)" : 'ninguna');
            } catch (Throwable $e) {
                $this->fila('Raspberry Pi: lector', false, 'no responde en ' . $base . ': ¿está encendida y en la red SICCAM-QR?');
            }
        } else {
            $this->fila('Raspberry Pi', null, 'RASPBERRY_STREAM_URL no está en .env');
        }

        // 5. Datos y horarios configurados
        $this->newLine();
        $activos = Estudiante::where('estado', 'activo')->count();
        $conApp = Tutor::where('estado', 'activo')->whereNotNull('fcm_token')->count();
        $this->line('  Estudiantes activos: <options=bold>' . $activos . '</> · Tutores con la app: <options=bold>' . $conApp . '</>');
        $this->line('  Presente hasta las <options=bold>' . Asistencia::horaLimite(now())->format('H:i')
            . '</> · Ausencias a las <options=bold>' . Asistencia::horaAusencias(now())->format('H:i') . '</>');

        $this->resumen();

        return $this->fallas ? self::FAILURE : self::SUCCESS;
    }

    /** $detalle se muestra siempre; $siFalta solo cuando el punto no está listo. */
    private function fila(string $nombre, ?bool $ok, string $detalle, string $siFalta = ''): void
    {
        if ($ok !== true && $siFalta !== '') {
            $detalle = $siFalta;
        }
        $marca = match ($ok) {
            true    => '<fg=green;options=bold>OK</>',
            false   => '<fg=red;options=bold>FALTA</>',
            default => '<fg=yellow;options=bold>¿?</>',
        };
        if ($ok === false) {
            $this->fallas++;
        }
        $this->components->twoColumnDetail($nombre . ' <fg=gray>' . $detalle . '</>', $marca);
    }

    private function resumen(): void
    {
        $this->newLine();
        $this->fallas
            ? $this->components->error("Revise los {$this->fallas} punto(s) marcados con FALTA.")
            : $this->components->info('Todo listo para la demostración.');
    }

    private function puerto(string $host, int $puerto): bool
    {
        $conexion = @fsockopen($host, $puerto, $codigo, $mensaje, 3);
        if ($conexion) {
            fclose($conexion);
            return true;
        }
        return false;
    }

    /** Líneas de comando de los procesos php (null si no se pueden leer). */
    private function procesosPhp(): ?string
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return shell_exec('ps -eo args 2>/dev/null') ?: null;
        }
        $salida = shell_exec('powershell -NoProfile -Command "Get-CimInstance Win32_Process -Filter \"Name=\'php.exe\'\" | Select-Object -ExpandProperty CommandLine" 2>NUL');

        return $salida === null ? null : (string) $salida;
    }
}
