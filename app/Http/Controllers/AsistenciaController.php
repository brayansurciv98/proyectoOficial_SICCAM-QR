<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Estudiante;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AsistenciaController extends Controller
{
    public function index(Request $request)
    {
        // Fecha por defecto es hoy si no se envía filtro
        $fecha = $request->get('fecha', Carbon::today()->format('Y-m-d'));
        
        $query = Asistencia::with('estudiante')
            ->whereDate('fecha', $fecha);

        // Filtro por curso a través de la relación de estudiante
        if ($request->filled('curso')) {
            $query->whereHas('estudiante', function ($q) use ($request) {
                $q->where('curso', $request->curso);
            });
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtro por nombre o código de estudiante
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->whereHas('estudiante', function ($q) use ($buscar) {
                $q->where('codigo_estudiante', 'LIKE', "%{$buscar}%")
                  ->orWhere('nombres', 'LIKE', "%{$buscar}%")
                  ->orWhere('apellidos', 'LIKE', "%{$buscar}%");
            });
        }

        $asistencias = $query->orderBy('hora_entrada', 'desc')->paginate(15);

        return view('asistencias.index', compact('asistencias', 'fecha'));
    }

    public function show(Asistencia $asistencia)
    {
        $asistencia->load('estudiante.tutores');
        return view('asistencias.show', compact('asistencia'));
    }
}