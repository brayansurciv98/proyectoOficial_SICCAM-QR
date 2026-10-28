<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

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

    public const COLEGIO_DEFECTO = 'Unidad Educativa René Barrientos Ortuño "A"';

    /**
     * Nombre de la institución (Configuración → Datos Institución).
     * Corto: "Unidad Educativa" se abrevia "U.E." (encabezados y credenciales).
     */
    public static function colegio(bool $corto = false): string
    {
        try {
            $nombre = static::valor('colegio_nombre', self::COLEGIO_DEFECTO);
        } catch (QueryException $e) {
            // Sin tabla de configuración (instalación nueva): el login igual debe mostrarse
            $nombre = self::COLEGIO_DEFECTO;
        }

        return $corto ? preg_replace('/^Unidad Educativa\s+/iu', 'U.E. ', $nombre) : $nombre;
    }
}
