<?php

namespace App\Http\Controllers;

use App\Models\Examen;
use App\Models\Docente;
use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExamenController extends Controller
{
    public function index()
    {
        $examenes = Examen::with(['docente', 'materia'])
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'asc')
            ->get();

        return view('examenes.index', compact('examenes'));
    }

    public function create()
    {
        $docentes = Docente::with('materia')
            ->where('estado', 'activo')
            ->orderBy('apellidos')
            ->get();

        $materias = Materia::orderBy('nombre')->get();

        return view('examenes.create', compact('docentes', 'materias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'docente_id'  => ['required', 'exists:docentes,id'],
            'materia_id'  => ['required', 'exists:materias,id'],
            'curso'       => ['required', 'integer', 'min:1', 'max:6'],
            'paralelo'    => ['required', 'in:A,B,C'],
            'titulo'      => ['required', 'string', 'max:150'],
            'fecha'       => ['required', 'date'],
            'hora'        => ['nullable', 'date_format:H:i'],
            'observacion' => ['nullable', 'string', 'max:500'],
        ], [
            'docente_id.required' => 'Seleccione un docente.',
            'materia_id.required' => 'Seleccione una materia.',
            'curso.min'           => 'El curso debe ser entre 1 y 6.',
            'curso.max'           => 'El curso debe ser entre 1 y 6.',
            'paralelo.in'         => 'El paralelo solo puede ser A, B o C.',
            'titulo.required'     => 'El título del examen es obligatorio.',
            'fecha.required'      => 'La fecha es obligatoria.',
        ]);

        Examen::create([
            'docente_id'  => $request->docente_id,
            'materia_id'  => $request->materia_id,
            'curso'       => (int) $request->curso,
            'paralelo'    => strtoupper($request->paralelo),
            'titulo'      => trim($request->titulo),
            'fecha'       => $request->fecha,
            'hora'        => $request->hora,
            'observacion' => $request->observacion ? trim($request->observacion) : null,
        ]);

        return redirect()->route('examenes.index')
            ->with('success', 'Examen programado correctamente.');
    }

    public function destroy(Examen $examen)
    {
        $examen->delete();

        return redirect()->route('examenes.index')
            ->with('success', 'Examen eliminado correctamente.');
    }
}