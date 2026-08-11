<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tutor extends Model
{
    protected $table = 'tutores';

    protected $fillable = [
        'nombres',
        'apellidos',
        'ci',
        'telefono',
        'email',
        'password',
        'estado',
    ];

    public function estudiantes()
    {
        return $this->belongsToMany(Estudiante::class, 'estudiante_tutor', 'tutor_id', 'estudiante_id')
                    ->withPivot('parentesco', 'es_principal')
                    ->withTimestamps();
    }
}