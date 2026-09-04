<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    use HasFactory;

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