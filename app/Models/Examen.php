<?php

namespace App\Models;

use App\Services\AvisoTutores;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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

    protected static function booted(): void
    {
        // Cada examen nuevo (desde admin o docente) se avisa a los padres de ese curso
        static::created(function (Examen $examen) {
            try {
                app(AvisoTutores::class)->examen($examen);
            } catch (\Throwable $e) {
                // Si falla el aviso, el examen igual queda guardado
                Log::warning('No se pudo avisar el examen ' . $examen->id . ': ' . $e->getMessage());
            }
        });
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class);
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }
}