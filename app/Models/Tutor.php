<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tutor extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'tutores';

    protected $fillable = [
        'nombres',
        'apellidos',
        'ci',
        'telefono',
        'email',
        'password',
        'clave_inicial',
        'estado',
    ];

    protected $hidden = [
        'password',
        'clave_inicial',
    ];

    public function estudiantes(): BelongsToMany
    {
        return $this->belongsToMany(
            Estudiante::class,
            'estudiante_tutor',
            'tutor_id',
            'estudiante_id'
        )->withPivot('parentesco', 'es_principal')
         ->withTimestamps();
    }
}