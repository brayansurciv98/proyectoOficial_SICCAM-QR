<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Asistencia;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $hoy = Carbon::today();

        // 1. Total de estudiantes activos registrados
        $totalEstudiantes = Estudiante::where('estado', 'activo')->count();

        // 2. Asistencias registradas el día de hoy (A tiempo / Presentes)
        $asistenciasHoy = Asistencia::whereDate('fecha', $hoy)
            ->whereIn('estado', ['presente', 'a_tiempo'])
            ->count();

        // 3. Tardanzas de hoy
        $tardanzasHoy = Asistencia::whereDate('fecha', $hoy)
            ->where('estado', 'atraso')
            ->count();

        // 4. Ausencias de hoy (Estudiantes activos que no registraron marcas hoy)
        $estudiantesPresentesHoy = Asistencia::whereDate('fecha', $hoy)
            ->pluck('estudiante_id')
            ->unique();

        $ausenciasHoy = Estudiante::where('estado', 'activo')
            ->whereNotIn('id', $estudiantesPresentesHoy)
            ->count();

        // 5. Últimas 5 asistencias en tiempo real
        $ultimasAsistencias = Asistencia::with('estudiante')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'totalEstudiantes',
            'asistenciasHoy',
            'tardanzasHoy',
            'ausenciasHoy',
            'ultimasAsistencias'
        ));
    }
}