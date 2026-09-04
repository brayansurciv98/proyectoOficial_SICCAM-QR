<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Asistencia;
use App\Events\AsistenciaRegistrada;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AsistenciaApiController extends Controller
{
    public function marcarAsistencia(Request $request)
    {
        // Validar token de seguridad de la Raspberry Pi
        $tokenEsperado = config('services.raspberry.token', 'siccam_qr_secret_key_2026');
        if ($request->header('X-Raspberry-Token') !== $tokenEsperado) {
            return response()->json([
                'success' => false,
                'message' => 'Token de autorización inválido'
            ], 401);
        }

        // Validar parámetro recibido
        $request->validate([
            'codigo_estudiante' => 'required|string'
        ]);

        $codigo = $request->input('codigo_estudiante');

        // Buscar estudiante activo por código
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

        $hoy = Carbon::today();
        $horaActual = Carbon::now();

        // Verificar si ya registró asistencia hoy
        $asistenciaExistente = Asistencia::where('estudiante_id', $estudiante->id)
            ->whereDate('fecha', $hoy)
            ->first();

        if ($asistenciaExistente) {
            return response()->json([
                'success' => true,
                'duplicado' => true,
                'message' => 'Asistencia ya registrada previamente hoy',
                'estudiante' => [
                    'nombre' => $estudiante->nombres . ' ' . $estudiante->apellidos,
                    'curso' => $estudiante->curso . 'º de Secundaria - ' . $estudiante->paralelo,
                    'hora_ingreso' => $asistenciaExistente->hora_ingreso
                ]
            ], 200);
        }

        // Determinar estado ('presente' o 'tardanza')
        $horaLimite = Carbon::today()->setTime(8, 0, 0);
        $estado = $horaActual->gt($horaLimite) ? 'tardanza' : 'presente';

        // Guardar asistencia en MySQL
        $asistencia = Asistencia::create([
            'estudiante_id'  => $estudiante->id,
            'fecha'          => $hoy->toDateString(),
            'hora_ingreso'   => $horaActual->toTimeString(),
            'estado'         => $estado,
            'registrado_por' => 'QR_Raspberry'
        ]);

        // Disparar evento WebSocket (Laravel Reverb)
        event(new AsistenciaRegistrada($asistencia));

        return response()->json([
            'success' => true,
            'duplicado' => false,
            'message' => 'Asistencia registrada correctamente',
            'estudiante' => [
                'nombre' => $estudiante->nombres . ' ' . $estudiante->apellidos,
                'curso' => $estudiante->curso . 'º de Secundaria - ' . $estudiante->paralelo,
                'hora_ingreso' => $horaActual->format('H:i:s'),
                'estado' => ucfirst($estado)
            ]
        ], 201);
    }
}