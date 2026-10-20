<?php

namespace App\Http\Controllers;

use App\Models\Examen;
use App\Models\Docente;
use App\Models\Horario;
use App\Models\Materia;
use App\Services\ValidadorExamen;
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

        // Clases de todos los docentes: el formulario avisa si el examen no cae en una
        $horariosJs = $this->horariosParaFormulario(
            Horario::where('estado', 'activo')->get()
        );

        return view('examenes.create', compact('docentes', 'materias', 'horariosJs'));
    }

    public function store(Request $request, ValidadorExamen $validador)
    {
        $request->validate([
            'docente_id'  => ['required', 'exists:docentes,id'],
            'materia_id'  => ['required', 'exists:materias,id'],
            'curso'       => ['required', 'integer', 'min:1', 'max:6'],
            'paralelo'    => ['required', 'in:A,B,C'],
            'titulo'      => ['required', 'string', 'max:150'],
            'fecha'       => ['required', 'date', 'after_or_equal:today'],
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
            'fecha.after_or_equal' => 'La fecha del examen no puede ser anterior a hoy.',
        ]);

        $docente = Docente::findOrFail($request->docente_id);

        // La materia debe ser la del docente (igual que al crear horarios)
        if ((int) $docente->materia_id !== (int) $request->materia_id) {
            return back()
                ->withErrors(['materia_id' => 'La materia no corresponde al docente seleccionado.'])
                ->withInput();
        }

        // Solo en un día y hora en que el docente tiene clase con ese curso
        $hora = $validador->validar(
            $docente,
            (int) $request->curso,
            strtoupper($request->paralelo),
            $request->fecha,
            $request->hora
        );

        Examen::create([
            'docente_id'  => $docente->id,
            'materia_id'  => $docente->materia_id,
            'curso'       => (int) $request->curso,
            'paralelo'    => strtoupper($request->paralelo),
            'titulo'      => trim($request->titulo),
            'fecha'       => $request->fecha,
            'hora'        => $hora,
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

    public function indexDocente()
    {
        /** @var \App\Models\Docente $docente */
        $docente = Auth::guard('docente')->user();

        $examenes = Examen::with(['materia'])
            ->where('docente_id', $docente->id)
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'asc')
            ->get();

        return view('docentes.examenes_index', compact('examenes', 'docente'));
    }

    public function createDocente()
    {
        /** @var \App\Models\Docente $docente */
        $docente = Auth::guard('docente')->user();
        $docente->load(['materia', 'cursos']);

        // Sus clases: solo puede programar exámenes en estos días y horas
        $horarios = $docente->horarios()
            ->where('estado', 'activo')
            ->orderBy('dia')
            ->orderBy('hora_inicio')
            ->get();

        $horariosJs = $this->horariosParaFormulario($horarios);

        return view('docentes.examenes_create', compact('docente', 'horarios', 'horariosJs'));
    }

    public function storeDocente(Request $request, ValidadorExamen $validador)
    {
        /** @var \App\Models\Docente $docente */
        $docente = Auth::guard('docente')->user();
        $docente->load('cursos');

        $request->validate([
            'curso'       => ['required', 'integer', 'min:1', 'max:6'],
            'paralelo'    => ['required', 'in:A,B,C'],
            'titulo'      => ['required', 'string', 'max:150'],
            'fecha'       => ['required', 'date', 'after_or_equal:today'],
            'hora'        => ['nullable', 'date_format:H:i'],
            'observacion' => ['nullable', 'string', 'max:500'],
        ], [
            'fecha.after_or_equal' => 'La fecha del examen no puede ser anterior a hoy.',
        ]);

        $permitido = $docente->cursos->contains(
            fn ($c) => (int) $c->curso === (int) $request->curso
                && $c->paralelo === $request->paralelo
        );

        if (!$permitido) {
            return back()
                ->withErrors(['curso' => 'No tienes asignado ese curso/paralelo.'])
                ->withInput();
        }

        // Solo en un día y hora en que tiene clase con ese curso
        $hora = $validador->validar(
            $docente,
            (int) $request->curso,
            $request->paralelo,
            $request->fecha,
            $request->hora
        );

        Examen::create([
            'docente_id'  => $docente->id,
            'materia_id'  => $docente->materia_id,
            'curso'       => (int) $request->curso,
            'paralelo'    => $request->paralelo,
            'titulo'      => trim($request->titulo),
            'fecha'       => $request->fecha,
            'hora'        => $hora,
            'observacion' => $request->observacion,
        ]);

        return redirect()
            ->route('docente.examenes.index')
            ->with('success', 'Examen programado correctamente.');
    }

    public function destroyDocente(Examen $examen)
    {
        /** @var \App\Models\Docente $docente */
        $docente = Auth::guard('docente')->user();

        if ((int) $examen->docente_id !== (int) $docente->id) {
            return redirect()
                ->route('docente.examenes.index')
                ->withErrors(['examen' => 'No puedes eliminar este examen.']);
        }

        $examen->delete();

        return redirect()
            ->route('docente.examenes.index')
            ->with('success', 'Examen eliminado correctamente.');
    }

    /**
     * Datos mínimos de los horarios para public/js/examenes-horario.js
     */
    private function horariosParaFormulario($horarios): array
    {
        return $horarios->map(fn ($h) => [
            'docente'  => $h->docente_id,
            'dia'      => (int) $h->dia,
            'inicio'   => substr($h->hora_inicio, 0, 5),
            'fin'      => substr($h->hora_fin, 0, 5),
            'curso'    => (string) $h->curso,
            'paralelo' => $h->paralelo,
        ])->values()->all();
    }
}