<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
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
}