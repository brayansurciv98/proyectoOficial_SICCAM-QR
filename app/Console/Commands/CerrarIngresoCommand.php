<?php

namespace App\Console\Commands;

use App\Models\Asistencia;
use App\Models\Estudiante;
use App\Services\AvisoTutores;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CerrarIngresoCommand extends Command
{
    protected $signature = 'asistencias:cerrar-ingreso {--forzar : Ejecutar aunque no haya pasado la hora límite}';

    protected $description = 'Marca como ausentes a los estudiantes activos sin ingreso hoy y avisa a sus tutores';

    public function handle(AvisoTutores $aviso): int
    {
        $ahora = Carbon::now();
        $limite = Asistencia::horaLimite($ahora);

        if ($ahora->isWeekend()) {
            $this->info('Hoy no hay clases (fin de semana).');
            return self::SUCCESS;
        }

        if ($ahora->lt($limite) && !$this->option('forzar')) {
            $this->error('Todavía no pasó la hora límite (' . $limite->format('H:i') . '). Use --forzar para ejecutarlo igual.');
            return self::FAILURE;
        }

        $hoy = $ahora->toDateString();
        $conRegistro = Asistencia::whereDate('fecha', $hoy)->pluck('estudiante_id');

        $faltantes = Estudiante::where('estado', 'activo')
            ->whereNotIn('id', $conRegistro)
            ->get();

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
                $aviso->asistencia($asistencia);
            }
        }

        $this->info("Ausentes marcados: {$marcados}");

        return self::SUCCESS;
    }
}