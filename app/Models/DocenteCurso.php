<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocenteCurso extends Model
{
    protected $table = 'docente_curso';

    protected $fillable = ['docente_id', 'curso', 'paralelo'];

    public function docente()
    {
        return $this->belongsTo(Docente::class);
    }
}