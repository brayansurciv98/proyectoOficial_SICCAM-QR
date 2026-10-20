<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Horario extends Model
{
    protected $fillable = [
        'dia',
        'bloque',
        'hora_inicio',
        'hora_fin',
        'curso',
        'paralelo',
        'materia_id',
        'docente_id',
        'estado',
    ];

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class);
    }

    public function asistenciasClase()
    {
        return $this->hasMany(AsistenciaClase::class);
    }

    public static function horasBloque(int $bloque): array
    {
        return match ($bloque) {
            1 => ['13:30:00', '15:00:00'],
            2 => ['15:10:00', '16:40:00'],
            3 => ['16:50:00', '17:50:00'],
            4 => ['17:50:00', '18:45:00'],
            default => ['13:30:00', '15:00:00'],
        };
    }

    public static function nombreDia(int $dia): string
    {
        return match ($dia) {
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            default => '—',
        };
    }

    /**
     * Clases de hoy del docente, cada una con:
     *  - estado_clase: en_curso | proxima | pendiente | terminada
     *  - asistencia_tomada: true si ya guardó la asistencia de esa clase hoy
     */
    public static function jornadaDe(Docente $docente): Collection
    {
        $ahora = now();
        $hora = $ahora->format('H:i:s');

        $clases = static::with('materia')
            ->where('docente_id', $docente->id)
            ->where('dia', $ahora->dayOfWeekIso)
            ->where('estado', 'activo')
            ->orderBy('hora_inicio')
            ->get();

        $hayProxima = false;

        foreach ($clases as $clase) {
            if ($hora >= $clase->hora_inicio && $hora < $clase->hora_fin) {
                $clase->estado_clase = 'en_curso';
            } elseif ($hora < $clase->hora_inicio) {
                $clase->estado_clase = $hayProxima ? 'pendiente' : 'proxima';
                $hayProxima = true;
            } else {
                $clase->estado_clase = 'terminada';
            }

            $clase->asistencia_tomada = AsistenciaClase::where('horario_id', $clase->id)
                ->whereDate('fecha', $ahora->toDateString())
                ->exists();
        }

        return $clases;
    }
}