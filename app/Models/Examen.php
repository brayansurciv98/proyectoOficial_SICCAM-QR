<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Examen extends Model
{
    protected $table = 'examenes';

    protected $fillable = [
        'docente_id', 'materia_id', 'curso', 'paralelo',
        'titulo', 'fecha', 'hora', 'observacion',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function docente()
    {
        return $this->belongsTo(Docente::class);
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }
}