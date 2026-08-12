<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comunicado extends Model
{
    use HasFactory;

    protected $table = 'comunicados';

    protected $fillable = [
        'titulo',
        'mensaje',
        'destinatario',
        'curso_destino',
        'paralelo_destino',
        'tutor_id',
        'canal',
        'estado',
        'user_id',
    ];

    public function tutor()
    {
        return $this->belongsTo(Tutor::class, 'tutor_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}