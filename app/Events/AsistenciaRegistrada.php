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

    // El canal es público: enviar solo lo que muestra el panel (sin CI, fecha de nacimiento ni qr_data)
    public function broadcastWith(): array
    {
        $estudiante = $this->asistencia->estudiante;

        return [
            'asistencia' => [
                'id'           => $this->asistencia->id,
                'fecha'        => (string) $this->asistencia->fecha,
                'hora_ingreso' => $this->asistencia->hora_ingreso,
                'estado'       => $this->asistencia->estado,
                'estudiante'   => [
                    'codigo_estudiante' => $estudiante?->codigo_estudiante,
                    'nombres'           => $estudiante?->nombres,
                    'apellidos'         => $estudiante?->apellidos,
                    'curso'             => $estudiante?->curso,
                    'paralelo'          => $estudiante?->paralelo,
                    'genero'            => $estudiante?->genero, // para el ícono de la fila en tiempo real
                ],
            ],
        ];
    }
}