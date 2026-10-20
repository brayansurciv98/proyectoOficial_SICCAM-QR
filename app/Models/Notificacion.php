<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = [
        'tutor_id',
        'estudiante_id',
        'tipo',
        'titulo',
        'mensaje',
        'fecha',
        'enviada_push',
        'leida_en',
    ];

    protected $casts = [
        'fecha'        => 'date',
        'enviada_push' => 'boolean',
        'leida_en'     => 'datetime',
    ];

    public function tutor()
    {
        return $this->belongsTo(Tutor::class);
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }
}