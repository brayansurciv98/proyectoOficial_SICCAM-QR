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
        $fecha = $request->get('fecha', Carbon::today()->format('Y-m-d'));

        $query = Asistencia::with('estudiante')
            ->whereDate('fecha', $fecha);

        if ($request->filled('curso')) {
            $query->whereHas('estudiante', function ($q) use ($request) {
                $q->where('curso', $request->curso);
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->whereHas('estudiante', function ($q) use ($buscar) {
                $q->where('codigo_estudiante', 'LIKE', "%{$buscar}%")
                  ->orWhere('nombres', 'LIKE', "%{$buscar}%")
                  ->orWhere('apellidos', 'LIKE', "%{$buscar}%");
            });
        }

        $asistencias = $query->orderBy('hora_ingreso', 'desc')->paginate(15);

        return view('asistencias.index', compact('asistencias', 'fecha'));
    }

    public function show(Asistencia $asistencia)
    {
        $asistencia->load('estudiante.tutores');
        return view('asistencias.show', compact('asistencia'));
    }

    public function manual(Request $request)
    {
        $curso = $request->get('curso');
        $paralelo = $request->get('paralelo');
        $fecha = $request->get('fecha', Carbon::today()->format('Y-m-d'));

        $estudiantes = collect();
        $asistenciasHoy = collect();

        if ($curso) {
            $estudiantes = Estudiante::where('curso', $curso)
                ->when($paralelo, fn ($q) => $q->where('paralelo', $paralelo))
                ->where('estado', 'activo')
                ->orderBy('apellidos')
                ->orderBy('nombres')
                ->get();

            $asistenciasHoy = Asistencia::whereDate('fecha', $fecha)
                ->whereIn('estudiante_id', $estudiantes->pluck('id'))
                ->get()
                ->keyBy('estudiante_id');
        }

        return view('asistencias.manual', compact(
            'estudiantes', 'curso', 'paralelo', 'fecha', 'asistenciasHoy'
        ));
    }

    public function storeManual(Request $request)
    {
        $request->validate([
            'fecha'     => ['required', 'date'],
            'estados'   => ['required', 'array'],
            'estados.*' => ['nullable', 'in:presente,tardanza,falta'],
        ]);

        $fecha = $request->fecha;
        $hora  = now()->format('H:i:s');
        $registrados = 0;

        foreach ($request->estados as $estudianteId => $estado) {
            if (!$estado) {
                continue;
            }

            $existe = Asistencia::where('estudiante_id', $estudianteId)
                ->whereDate('fecha', $fecha)
                ->first();

            if ($existe) {
                $existe->update([
                    'estado'       => $estado,
                    'hora_ingreso' => $existe->hora_ingreso ?? $hora,
                    'observacion'  => 'Registro manual',
                ]);
            } else {
                Asistencia::create([
                    'estudiante_id' => $estudianteId,
                    'fecha'         => $fecha,
                    'hora_ingreso'  => $hora,
                    'estado'        => $estado,
                    'observacion'   => 'Registro manual',
                ]);
            }

            $registrados++;
        }

        return back()->with('success', "Se actualizaron {$registrados} registros de asistencia.");
    }
}