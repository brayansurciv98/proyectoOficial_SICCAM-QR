<?php
namespace App\Events;

use App\Models\Asistencia;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // Transmisión instantánea
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AsistenciaRegistrada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $asistencia;

    public function __construct(Asistencia $asistencia)
    {
        // Cargar la relación del estudiante para enviarla completa al frontend
        $this->asistencia = $asistencia->load('estudiante');
    }

    // Nombre del canal público de WebSockets
    public function broadcastOn(): array
    {
        return [
            new Channel('asistencias-channel'),
        ];
    }

    // Nombre con el que el JavaScript escuchará el evento
    public function broadcastAs(): string
    {
        return 'AsistenciaRegistrada';
    }
}