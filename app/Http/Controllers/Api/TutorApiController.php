<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tutor;
use App\Models\Asistencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Examen;

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

        // Validar password hasheado o clave_inicial
        $passwordOk = false;

        if ($tutor && !empty($tutor->password) && Hash::check($password, $tutor->password)) {
            $passwordOk = true;
        } elseif ($tutor && !empty($tutor->clave_inicial) && hash_equals((string) $tutor->clave_inicial, $password)) {
            $passwordOk = true;
        }

        // Mismo mensaje si el usuario no existe o la contraseña es incorrecta:
        // así no se puede averiguar qué CI o correos están registrados
        if (!$passwordOk) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario o contraseña incorrectos'
            ], 401);
        }

        if ($tutor->estado !== 'activo') {
            return response()->json([
                'success' => false,
                'message' => 'Su cuenta está inactiva. Comuníquese con la unidad educativa.'
            ], 403);
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
    // POST /api/tutor/fcm-token
    public function guardarFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => ['required', 'string', 'max:512'],
        ]);

        // Si otro tutor usaba antes este celular, deja de recibir sus avisos
        Tutor::where('fcm_token', $request->fcm_token)
            ->where('id', '!=', $request->user()->id)
            ->update(['fcm_token' => null]);

        $request->user()->update(['fcm_token' => $request->fcm_token]);

        return response()->json(['success' => true]);
    }

    // GET /api/tutor/notificaciones
    public function getNotificaciones(Request $request)
    {
        $notificaciones = $request->user()->notificaciones()
            ->latest()
            ->take(50)
            ->get()
            ->map(fn ($n) => [
                'id'            => $n->id,
                'tipo'          => $n->tipo,
                'titulo'        => $n->titulo,
                'mensaje'       => $n->mensaje,
                'fecha'         => $n->fecha?->toDateString(),
                'hora'          => $n->created_at->format('H:i'),
                'estudiante_id' => $n->estudiante_id,
                'leida'         => $n->leida_en !== null,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'notificaciones' => $notificaciones,
        ]);
    }

    // POST /api/tutor/notificaciones/leidas
    public function marcarLeidas(Request $request)
    {
        $request->user()->notificaciones()
            ->whereNull('leida_en')
            ->update(['leida_en' => now()]);

        return response()->json(['success' => true]);
    }

    // POST /api/tutor/logout
    public function logout(Request $request)
    {
        $request->user()->update(['fcm_token' => null]);
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true]);
    }
        // GET /api/tutor/examenes
    public function getExamenes(Request $request)
    {
        $hijos = $request->user()->estudiantes()
            ->where('estudiantes.estado', 'activo')
            ->get();

        if ($hijos->isEmpty()) {
            return response()->json(['success' => true, 'examenes' => []]);
        }

        $examenes = Examen::with(['materia', 'docente'])
            ->where('fecha', '>=', today()->subDays(7))
            ->where(function ($q) use ($hijos) {
                foreach ($hijos as $hijo) {
                    $q->orWhere(fn ($q) => $q->where('curso', $hijo->curso)->where('paralelo', $hijo->paralelo));
                }
            })
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get()
            ->map(function ($examen) use ($hijos) {
                // A cuál de sus hijos le toca este examen
                $para = $hijos
                    ->filter(fn ($h) => (string) $h->curso === (string) $examen->curso && $h->paralelo === $examen->paralelo)
                    ->map(fn ($h) => $h->nombres)
                    ->values();

                return [
                    'id'          => $examen->id,
                    'titulo'      => $examen->titulo,
                    'materia'     => $examen->materia->nombre ?? '',
                    'docente'     => $examen->docente ? $examen->docente->nombres . ' ' . $examen->docente->apellidos : '',
                    'fecha'       => $examen->fecha->toDateString(),
                    'hora'        => $examen->hora ? substr($examen->hora, 0, 5) : null,
                    'observacion' => $examen->observacion,
                    'curso'       => $examen->curso . '° ' . $examen->paralelo,
                    'estudiantes' => $para,
                    // 0 = hoy, 1 = mañana, negativo = ya pasó
                    'dias'        => (int) today()->diffInDays($examen->fecha, false),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'examenes' => $examenes,
        ]);
    }
}