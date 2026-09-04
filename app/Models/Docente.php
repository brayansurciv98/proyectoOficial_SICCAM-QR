<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Docente extends Authenticatable
{
    protected $fillable = [
        'nombres', 'apellidos', 'ci', 'email', 'telefono',
        'materia_id', 'password', 'clave_inicial', 'estado',
    ];

    protected $hidden = ['password'];

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }

    public function cursos()
    {
        return $this->hasMany(DocenteCurso::class);
    }

    public function examenes()
    {
        return $this->hasMany(Examen::class);
    }
}