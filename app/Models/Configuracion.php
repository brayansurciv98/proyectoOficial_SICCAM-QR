<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    use HasFactory;

    protected $table = 'configuraciones';

    protected $fillable = [
        'clave',
        'valor',
        'grupo',
    ];

    /**
     * Devuelve el valor guardado para una clave o el valor por defecto.
     */
    public static function valor(string $clave, mixed $default = null): mixed
    {
        $valor = static::where('clave', $clave)->value('valor');

        return ($valor === null || $valor === '') ? $default : $valor;
    }
}
