<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tutor;
use App\Models\Asistencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TutorApiController extends Controller
{
    // POST /api/tutor/login
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = trim($request->login);
        $password = $request->password;

        // Buscar por CI o Email
        $tutor = Tutor::where('ci', $login)
            ->orWhere('email', $login)
            ->first();

        if (!$tutor) {
            return response()->json([
                'success' => false,
                'message' => 'Tutor no encontrado'
            ], 404);
        }

        if ($tutor->estado !== 'activo') {
            return response()->json([
                'success' => false,
                'message' => 'Tutor inactivo'
            ], 403);
        }

        // Validar password hasheado o clave_inicial
        $passwordOk = false;

        if (!empty($tutor->password) && Hash::check($password, $tutor->password)) {
            $passwordOk = true;
        } elseif (!empty($tutor->clave_inicial) && $password === $tutor->clave_inicial) {
            $passwordOk = true;
        }

        if (!$passwordOk) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        // Token Sanctum real
        $token = $tutor->createToken('app-tutor')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'token' => $token,
            'tutor' => [
                'id' => $tutor->id,
                'nombres' => $tutor->nombres,
                'apellidos' => $tutor->apellidos,
                'email' => $tutor->email,
                'ci' => $tutor->ci,
            ]
        ], 200);
    }

    // GET /api/tutor/hijos
    // GET /api/tutor/hijos
public function getHijos(Request $request)
{
    $tutor = $request->user();

    if (!$tutor) {
        return response()->json([
            'success' => false,
            'message' => 'No autenticado',
            'hijos' => []
        ], 401);
    }

    $hijos = $tutor->estudiantes()
        ->where('estudiantes.estado', 'activo')
        ->get()
        ->map(function ($est) {
            return [
                'id' => $est->id,
                'codigo' => $est->codigo_estudiante,
                'nombres' => $est->nombres,
                'apellidos' => $est->apellidos,
                'curso' => trim(($est->curso ?? '') . ' ' . ($est->paralelo ?? '')),
                'foto_url' => null,
            ];
        })
        ->values();

    return response()->json([
        'success' => true,
        'hijos' => $hijos
    ], 200);
}

    // GET /api/tutor/hijos/{id}/asistencias
    public function getAsistenciasHijo(Request $request, $id)
{
    $tutor = $request->user();

    if (!$tutor) {
        return response()->json([
            'success' => false,
            'message' => 'No autenticado',
            'asistencias' => []
        ], 401);
    }

    $estudiante = $tutor->estudiantes()
        ->where('estudiantes.id', $id)
        ->first();

    if (!$estudiante) {
        return response()->json([
            'success' => false,
            'message' => 'Estudiante no autorizado',
            'asistencias' => []
        ], 403);
    }

    $asistencias = Asistencia::where('estudiante_id', $id)
        ->orderBy('fecha', 'desc')
        ->orderBy('hora_ingreso', 'desc')
        ->get()
        ->map(function ($a) {
            return [
                'id' => $a->id,
                'fecha' => $a->fecha,
                'hora' => $a->hora_ingreso,
                'estado' => $a->estado,
                'observacion' => $a->observacion,
            ];
        })
        ->values();

    return response()->json([
        'success' => true,
        'estudiante' => [
            'id' => $estudiante->id,
            'codigo' => $estudiante->codigo_estudiante,
            'nombres' => $estudiante->nombres,
            'apellidos' => $estudiante->apellidos,
        ],
        'asistencias' => $asistencias
    ], 200);
}
}