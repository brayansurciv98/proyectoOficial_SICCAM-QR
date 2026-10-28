<?php

namespace App\Console\Commands;

use App\Models\Asistencia;
use App\Models\Configuracion;
use App\Models\Estudiante;
use App\Services\AvisoTutores;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CerrarIngresoCommand extends Command
{
    protected $signature = 'asistencias:cerrar-ingreso {--forzar : Ejecutar aunque no sea la hora de marcar ausencias}';

    protected $description = 'Marca como ausentes a los estudiantes activos sin ingreso hoy y avisa a sus tutores';

    public function handle(AvisoTutores $aviso): int
    {
        $ahora = Carbon::now();
        $hoy = $ahora->toDateString();
        $forzar = (bool) $this->option('forzar');

        if ($ahora->isWeekend()) {
            $this->info('Hoy no hay clases (fin de semana).');
            return self::SUCCESS;
        }

        // Se ejecuta cada minuto (routes/console.php) y actúa una sola vez al día,
        // a la hora de Configuración. Si la laptop estaba apagada a esa hora, lo hace al encenderse.
        $hora = Asistencia::horaAusencias($ahora);
        if (!$forzar && $ahora->lt($hora)) {
            $this->info('Todavía no es la hora de marcar ausencias (' . $hora->format('H:i') . '). Use --forzar para ejecutarlo igual.');
            return self::SUCCESS;
        }

        $llave = "ausencias-marcadas:{$hoy}";
        if (!$forzar && Cache::has($llave)) {
            return self::SUCCESS;
        }

        $conRegistro = Asistencia::whereDate('fecha', $hoy)->pluck('estudiante_id');

        $faltantes = Estudiante::where('estado', 'activo')
            ->whereNotIn('id', $conRegistro)
            ->get();

        // Configuración → "Avisar a los tutores las ausencias"
        $avisar = Configuracion::valor('notificar_ausencia', 'si') !== 'no';
        $marcados = 0;

        foreach ($faltantes as $estudiante) {
            // createOrFirst: si la Raspberry lo registra en el mismo instante, no se duplica
            $asistencia = Asistencia::createOrFirst(
                ['estudiante_id' => $estudiante->id, 'fecha' => $hoy],
                [
                    'estado'         => Asistencia::AUSENTE,
                    'hora_ingreso'   => null,
                    'registrado_por' => 'Cierre automatico',
                    'observacion'    => 'Sin ingreso a las ' . $ahora->format('H:i'),
                ]
            );

            if ($asistencia->wasRecentlyCreated) {
                $marcados++;
                if ($avisar) {
                    $aviso->asistencia($asistencia);
                }
            }
        }

        Cache::put($llave, true, $ahora->copy()->endOfDay());

        $this->info("Ausentes marcados: {$marcados}" . ($avisar ? '' : ' (sin aviso a los tutores: desactivado en Configuración)'));

        return self::SUCCESS;
    }
}
