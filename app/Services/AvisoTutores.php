<?php

namespace App\Services;

use App\Jobs\EnviarNotificacionPush;
use App\Models\Asistencia;
use App\Models\Comunicado;
use App\Models\Examen;
use App\Models\Notificacion;
use App\Models\Tutor;
use Carbon\Carbon;

/**
 * Avisa a los tutores de una inasistencia, tardanza, comunicado o examen ("presente" no se notifica).
 * Guarda el aviso (lo ve la app) y encola el push de Firebase.
 */
class AvisoTutores
{
    public function asistencia(Asistencia $asistencia): void
    {
        $tipo = match ($asistencia->estado) {
            Asistencia::AUSENTE  => 'ausencia',
            Asistencia::TARDANZA => 'tardanza',
            default              => null,
        };

        if ($tipo === null) {
            return;
        }

        $estudiante = $asistencia->estudiante()->with('tutores')->first();
        if (!$estudiante) {
            return;
        }

        $nombre = $estudiante->nombres . ' ' . $estudiante->apellidos;

        [$titulo, $mensaje] = $tipo === 'ausencia'
            ? ['Inasistencia', $nombre . ' no registró su ingreso hoy.']
            : ['Llegada con retraso', $nombre . ' ingresó a las ' . substr((string) $asistencia->hora_ingreso, 0, 5) . '.'];

        foreach ($estudiante->tutores as $tutor) {
            if ($tutor->estado !== 'activo') {
                continue;
            }

            $notificacion = Notificacion::createOrFirst(
                [
                    'tutor_id'      => $tutor->id,
                    'estudiante_id' => $estudiante->id,
                    'tipo'          => $tipo,
                    'fecha'         => Carbon::parse($asistencia->fecha)->toDateString(),
                ],
                ['titulo' => $titulo, 'mensaje' => $mensaje]
            );

            if ($notificacion->wasRecentlyCreated) {
                EnviarNotificacionPush::dispatch($notificacion->id);
            }
        }
    }

    /**
     * Envía un comunicado a todos los tutores, o solo a los de un curso.
     * Devuelve a cuántos tutores se envió.
     */
    public function comunicado(Comunicado $comunicado): int
    {
        $tutores = Tutor::where('estado', 'activo')
            ->whereHas('estudiantes', function ($q) use ($comunicado) {
                $q->where('estudiantes.estado', 'activo');

                if ($comunicado->destinatario === 'curso' && $comunicado->curso_destino) {
                    $q->where('estudiantes.curso', $comunicado->curso_destino);
                }
            })
            ->get();

        foreach ($tutores as $tutor) {
            $notificacion = Notificacion::create([
                'tutor_id'      => $tutor->id,
                'estudiante_id' => null,
                'tipo'          => 'comunicado',
                'titulo'        => $comunicado->titulo,
                'mensaje'       => $comunicado->mensaje,
                'fecha'         => now()->toDateString(),
            ]);

            EnviarNotificacionPush::dispatch($notificacion->id);
        }

        return $tutores->count();
    }

    /**
     * Avisa a los tutores de los estudiantes del curso y paralelo del examen.
     * Un aviso por tutor (si tiene dos hijos en ese curso, los nombra a ambos).
     */
    public function examen(Examen $examen): int
    {
        $examen->loadMissing('materia');

        $delCurso = function ($q) use ($examen) {
            $q->where('estudiantes.estado', 'activo')
                ->where('estudiantes.curso', $examen->curso)
                ->where('estudiantes.paralelo', $examen->paralelo);
        };

        $tutores = Tutor::where('estado', 'activo')
            ->whereHas('estudiantes', $delCurso)
            ->with(['estudiantes' => $delCurso])
            ->get();

        $materia = $examen->materia->nombre ?? 'Examen';
        $cuando = ucfirst($examen->fecha->locale('es')->isoFormat('dddd D/MM'))
            . ($examen->hora ? ' a las ' . substr($examen->hora, 0, 5) : '');
        $grupo = $examen->curso . '° ' . $examen->paralelo;

        foreach ($tutores as $tutor) {
            $hijos = $tutor->estudiantes->pluck('nombres')->implode(', ');

            $notificacion = Notificacion::create([
                'tutor_id'      => $tutor->id,
                'estudiante_id' => null,
                'tipo'          => 'examen',
                'titulo'        => 'Nuevo examen: ' . $materia,
                'mensaje'       => "{$examen->titulo} — {$cuando} ({$hijos}, {$grupo}).",
                'fecha'         => now()->toDateString(),
            ]);

            EnviarNotificacionPush::dispatch($notificacion->id);
        }

        return $tutores->count();
    }
}