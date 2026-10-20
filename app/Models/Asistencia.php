<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    use HasFactory;

    // Únicos valores aceptados por la columna enum `estado`
    public const PRESENTE = 'presente';
    public const TARDANZA = 'tardanza';
    public const AUSENTE  = 'ausente';
    public const ESTADOS  = [self::PRESENTE, self::TARDANZA, self::AUSENTE];

    // Turno tarde: entrada 13:30 + 15 min de tolerancia (editable en Configuración)
    public const HORA_INGRESO_DEFECTO = '13:30';
    public const TOLERANCIA_DEFECTO   = 15;

    protected $table = 'asistencias';

    protected $fillable = [
        'estudiante_id',
        'fecha',
        'hora_ingreso',
        'estado',
        'observacion',
        'registrado_por',
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    /**
     * Hora hasta la que se considera "presente" (hora de ingreso + tolerancia).
     */
    public static function horaLimite(Carbon $fecha): Carbon
    {
        $horaIngreso = Configuracion::valor('hora_ingreso', self::HORA_INGRESO_DEFECTO);
        $tolerancia  = (int) Configuracion::valor('tolerancia_minutos', self::TOLERANCIA_DEFECTO);

        [$h, $m] = array_map('intval', explode(':', $horaIngreso) + [0, 0]);

        return $fecha->copy()->setTime($h, $m, 0)->addMinutes($tolerancia);
    }

    /**
     * Presente hasta la hora límite (inclusive); después, tardanza.
     */
    public static function estadoPorHora(Carbon $momento): string
    {
        return $momento->gt(self::horaLimite($momento)) ? self::TARDANZA : self::PRESENTE;
    }
}
