<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    protected $table = 'estudiantes';

    protected $fillable = [
        'codigo_estudiante',
        'nombres',
        'apellidos',
        'ci',
        'fecha_nacimiento',
        'genero',
        'curso',
        'paralelo',
        'foto',
        'qr_data',
        'qr_imagen',
        'estado',
    ];

    public function tutores()
    {
        return $this->belongsToMany(Tutor::class, 'estudiante_tutor', 'estudiante_id', 'tutor_id')
                    ->withPivot('parentesco', 'es_principal')
                    ->withTimestamps();
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }
}