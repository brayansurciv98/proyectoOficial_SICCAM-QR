<?php

namespace App\Jobs;

use App\Models\Notificacion;
use App\Services\FirebaseNotificador;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Se ejecuta con "php artisan queue:work" para no hacer esperar a la Raspberry.
 */
class EnviarNotificacionPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 120];

    public function __construct(public int $notificacionId)
    {
    }

    public function handle(FirebaseNotificador $firebase): void
    {
        $notificacion = Notificacion::with('tutor')->find($this->notificacionId);

        if (!$notificacion || $notificacion->enviada_push || !$notificacion->tutor?->fcm_token) {
            return;
        }

        $resultado = $firebase->enviar(
            $notificacion->tutor->fcm_token,
            $notificacion->titulo,
            $notificacion->mensaje,
            [
                'tipo'            => $notificacion->tipo,
                'notificacion_id' => $notificacion->id,
                'estudiante_id'   => $notificacion->estudiante_id,
            ]
        );

        if ($resultado === 'enviado') {
            $notificacion->update(['enviada_push' => true]);
        } elseif ($resultado === 'invalido') {
            $notificacion->tutor->update(['fcm_token' => null]);
        } elseif ($resultado === 'error') {
            // Se reintenta a los 30 s y a los 2 min
            throw new \RuntimeException('No se pudo enviar el push de la notificación ' . $notificacion->id);
        }
    }
}