<?php

namespace App\Services;

use App\Models\Docente;
use App\Models\Examen;
use App\Models\Horario;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Un examen solo se puede programar el día y a la hora en que el docente
 * tiene clase con ese curso y paralelo (según el módulo Horarios).
 * Se usa tanto en el panel del docente como en el del administrador.
 */
class ValidadorExamen
{
    /**
     * Devuelve la hora del examen (HH:MM). Si no se indicó hora,
     * usa el inicio de la primera clase del docente con ese curso ese día.
     *
     * @throws ValidationException con un mensaje claro para el formulario
     */
    public function validar(Docente $docente, int $curso, string $paralelo, string $fecha, ?string $hora): string
    {
        $dia = Carbon::parse($fecha);
        $nombreDia = $dia->locale('es')->dayName;
        $grupo = $curso . '° ' . $paralelo;

        $clases = Horario::where('docente_id', $docente->id)
            ->where('curso', $curso)
            ->where('paralelo', $paralelo)
            ->where('dia', $dia->dayOfWeekIso) // 1 = lunes ... 5 = viernes
            ->where('estado', 'activo')
            ->orderBy('hora_inicio')
            ->get();

        // 1. Ese día no tiene clase con ese curso
        if ($clases->isEmpty()) {
            $diasConClase = Horario::where('docente_id', $docente->id)
                ->where('curso', $curso)
                ->where('paralelo', $paralelo)
                ->where('estado', 'activo')
                ->orderBy('dia')
                ->pluck('dia')
                ->unique()
                ->map(fn ($d) => Horario::nombreDia((int) $d))
                ->implode(', ');

            throw ValidationException::withMessages([
                'fecha' => "{$docente->nombres} {$docente->apellidos} no tiene clase con {$grupo} el {$nombreDia}. "
                    . ($diasConClase
                        ? "Días con clase: {$diasConClase}."
                        : 'No tiene horario registrado con ese curso (regístrelo en Horarios).'),
            ]);
        }

        // 2. La hora debe caer dentro de una de sus clases de ese día
        $horaExamen = $hora ? substr($hora, 0, 5) : substr($clases->first()->hora_inicio, 0, 5);

        $bloque = $clases->first(fn ($c) => $horaExamen >= substr($c->hora_inicio, 0, 5)
            && $horaExamen < substr($c->hora_fin, 0, 5));

        if (!$bloque) {
            $horas = $clases
                ->map(fn ($c) => substr($c->hora_inicio, 0, 5) . ' a ' . substr($c->hora_fin, 0, 5))
                ->implode(', ');

            throw ValidationException::withMessages([
                'hora' => "A las {$horaExamen} no tiene clase con {$grupo}. El {$nombreDia} su clase es de {$horas}.",
            ]);
        }

        // 3. Un solo examen por curso en la misma clase y día
        $ocupado = Examen::where('curso', $curso)
            ->where('paralelo', $paralelo)
            ->whereDate('fecha', $dia->toDateString())
            ->whereTime('hora', '>=', $bloque->hora_inicio)
            ->whereTime('hora', '<', $bloque->hora_fin)
            ->exists();

        if ($ocupado) {
            throw ValidationException::withMessages([
                'hora' => "Ya hay un examen programado para {$grupo} el {$nombreDia} " . $dia->format('d/m') . ' en ese horario.',
            ]);
        }

        return $horaExamen;
    }
}