<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsistenciaClase extends Model
{
    protected $table = 'asistencias_clase';

    protected $fillable = [
        'horario_id',
        'estudiante_id',
        'docente_id',
        'fecha',
        'estado',
        'observacion',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function horario()
    {
        return $this->belongsTo(Horario::class);
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class);
    }
}