<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Asistencia;
use App\Events\AsistenciaRegistrada;
use App\Events\AsistenciaYaRegistrada;
use App\Services\AvisoTutores;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AsistenciaApiController extends Controller
{
    public function marcarAsistencia(Request $request)
    {
        $tokenEsperado = (string) config('services.raspberry.token');
        if (!hash_equals($tokenEsperado, (string) $request->header('X-Raspberry-Token'))) {
            return response()->json([
                'success' => false,
                'message' => 'Token de autorización inválido'
            ], 401);
        }

        $request->validate([
            'codigo_estudiante' => ['required', 'string', 'max:255'],
            // Hora real de la lectura: la envía la Raspberry al sincronizar su cola offline
            'fecha_hora' => [
                'nullable',
                'date',
                'after_or_equal:' . now()->subDays(7)->toDateTimeString(),
                'before_or_equal:' . now()->addMinutes(5)->toDateTimeString(),
            ],
        ]);

        // Acepta el contenido completo del QR (EST-2026-0001|Nombre|curso) o solo el código
        $codigo = trim(explode('|', $request->input('codigo_estudiante'))[0]);

        $estudiante = Estudiante::where('codigo_estudiante', $codigo)
            ->where('estado', 'activo')
            ->first();

        if (!$estudiante) {
            return response()->json([
                'success' => false,
                'message' => 'Estudiante no encontrado o inactivo',
                'codigo' => $codigo
            ], 404);
        }

        $momento = $request->filled('fecha_hora')
            ? Carbon::parse($request->input('fecha_hora'))->setTimezone(config('app.timezone'))
            : Carbon::now();

        $fecha  = $momento->toDateString();
        $estado = Asistencia::estadoPorHora($momento);

        $asistenciaExistente = Asistencia::where('estudiante_id', $estudiante->id)
            ->whereDate('fecha', $fecha)
            ->first();

        // ===== YA REGISTRADO ESE DÍA =====
        if ($asistenciaExistente && $asistenciaExistente->estado !== Asistencia::AUSENTE) {
            return $this->respuestaDuplicado($estudiante, $asistenciaExistente, $momento);
        }

        if ($asistenciaExistente) {
            // ===== ESTABA COMO AUSENTE Y LLEGÓ DESPUÉS =====
            // (marcado por el docente o por el cierre automático): se corrige con la lectura QR
            $asistenciaExistente->update([
                'hora_ingreso'   => $momento->toTimeString(),
                'estado'         => $estado,
                'registrado_por' => 'QR_Raspberry',
                'observacion'    => trim(($asistenciaExistente->observacion ? $asistenciaExistente->observacion . ' | ' : '') . 'Corregido por lectura QR'),
            ]);
            $asistencia = $asistenciaExistente;
            $codigoHttp = 200;
        } else {
            // ===== REGISTRO NUEVO =====
            try {
                $asistencia = Asistencia::create([
                    'estudiante_id'  => $estudiante->id,
                    'fecha'          => $fecha,
                    'hora_ingreso'   => $momento->toTimeString(),
                    'estado'         => $estado,
                    'registrado_por' => 'QR_Raspberry'
                ]);
            } catch (UniqueConstraintViolationException $e) {
                // Otra petición registró al mismo estudiante al mismo tiempo
                $existente = Asistencia::where('estudiante_id', $estudiante->id)
                    ->whereDate('fecha', $fecha)
                    ->firstOrFail();

                return $this->respuestaDuplicado($estudiante, $existente, $momento);
            }
            $codigoHttp = 201;
        }

        $this->emitir(new AsistenciaRegistrada($asistencia));

        // Avisar al tutor solo si llegó tarde ("presente" no se notifica)
        if ($asistencia->estado === Asistencia::TARDANZA) {
            try {
                app(AvisoTutores::class)->asistencia($asistencia);
            } catch (\Throwable $e) {
                Log::warning('No se pudo crear el aviso de tardanza: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'duplicado' => false,
            'actualizado' => $codigoHttp === 200,
            'message' => $codigoHttp === 200
                ? 'Asistencia corregida (estaba como ausente)'
                : 'Asistencia registrada correctamente',
            'estudiante' => [
                'nombre' => $estudiante->nombres . ' ' . $estudiante->apellidos,
                'curso' => $estudiante->curso . 'º de Secundaria - ' . $estudiante->paralelo,
                'hora_ingreso' => $momento->format('H:i:s'),
                'estado' => ucfirst($estado)
            ]
        ], $codigoHttp);
    }

    private function respuestaDuplicado(Estudiante $estudiante, Asistencia $asistencia, Carbon $momento)
    {
        $payload = [
            'success'   => true,
            'duplicado' => true,
            'message'   => 'Este QR ya fue registrado hoy',
            'estudiante' => [
                'codigo_estudiante' => $estudiante->codigo_estudiante,
                'nombre'    => $estudiante->nombres . ' ' . $estudiante->apellidos,
                'nombres'   => $estudiante->nombres,
                'apellidos' => $estudiante->apellidos,
                'curso'     => $estudiante->curso,
                'paralelo'  => $estudiante->paralelo,
            ],
            'hora_ingreso' => $asistencia->hora_ingreso
                ? Carbon::parse($asistencia->hora_ingreso)->format('H:i:s')
                : null,
            'estado' => $asistencia->estado,
        ];

        // Aviso en el panel solo para lecturas en vivo, no al sincronizar la cola offline
        if ($momento->diffInMinutes(Carbon::now(), true) <= 2) {
            $this->emitir(new AsistenciaYaRegistrada($payload));
        }

        return response()->json($payload, 200);
    }

    /**
     * Si Reverb no está encendido, la asistencia igual queda guardada.
     */
    private function emitir(object $evento): void
    {
        try {
            event($evento);
        } catch (\Throwable $e) {
            Log::warning('No se pudo emitir ' . class_basename($evento) . ': ' . $e->getMessage());
        }
    }
}