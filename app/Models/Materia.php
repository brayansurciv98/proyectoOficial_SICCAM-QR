<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    protected $fillable = ['nombre', 'codigo'];

    public function docentes()
    {
        return $this->hasMany(Docente::class);
    }
}