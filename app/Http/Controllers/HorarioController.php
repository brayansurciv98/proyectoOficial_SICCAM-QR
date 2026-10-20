<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use App\Models\Docente;
use App\Models\Materia;
use Illuminate\Http\Request;

class HorarioController extends Controller
{
        public function index(Request $request)
    {
        $query = Horario::with(['materia', 'docente'])
            ->orderBy('dia')
            ->orderBy('bloque')
            ->orderBy('curso')
            ->orderBy('paralelo');

        if ($request->filled('curso')) {
            $query->where('curso', $request->curso);
        }
        if ($request->filled('paralelo')) {
            $query->where('paralelo', $request->paralelo);
        }
        if ($request->filled('dia')) {
            $query->where('dia', $request->dia);
        }

        $horarios = $query->get();

        // Hoja de horario del curso (toda la semana) cuando se eligen curso y paralelo
        $grilla = null;
        if ($request->filled('curso') && $request->filled('paralelo')) {
            $grilla = [];
            $clasesCurso = Horario::with(['materia', 'docente'])
                ->where('curso', $request->curso)
                ->where('paralelo', $request->paralelo)
                ->where('estado', 'activo')
                ->get();

            foreach ($clasesCurso as $h) {
                $grilla[$h->bloque][$h->dia][] = $h;
            }
        }

        return view('horarios.index', compact('horarios', 'grilla'));
    }

    public function create()
    {
        $materias = Materia::orderBy('nombre')->get();
        $docentes = Docente::with('materia')
            ->where('estado', 'activo')
            ->orderBy('apellidos')
            ->get();

        return view('horarios.create', compact('materias', 'docentes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'dia'        => ['required', 'integer', 'min:1', 'max:5'],
            'bloque'     => ['required', 'integer', 'min:1', 'max:4'],
            'curso'      => ['required', 'integer', 'min:1', 'max:6'],
            'paralelo'   => ['required', 'in:A,B,C'],
            'materia_id' => ['required', 'exists:materias,id'],
            'docente_id' => ['required', 'exists:docentes,id'],
        ]);

        $docente = Docente::findOrFail($request->docente_id);

        if ((int) $docente->materia_id !== (int) $request->materia_id) {
            return back()
                ->withErrors(['materia_id' => 'La materia no corresponde al docente seleccionado.'])
                ->withInput();
        }

        [$horaInicio, $horaFin] = Horario::horasBloque((int) $request->bloque);

        $existe = Horario::where('dia', $request->dia)
            ->where('bloque', $request->bloque)
            ->where('curso', $request->curso)
            ->where('paralelo', $request->paralelo)
            ->exists();

        if ($existe) {
            return back()
                ->withErrors(['bloque' => 'Ya existe una materia en ese día, bloque, curso y paralelo.'])
                ->withInput();
        }

        Horario::create([
            'dia'         => (int) $request->dia,
            'bloque'      => (int) $request->bloque,
            'hora_inicio' => $horaInicio,
            'hora_fin'    => $horaFin,
            'curso'       => (int) $request->curso,
            'paralelo'    => $request->paralelo,
            'materia_id'  => $request->materia_id,
            'docente_id'  => $request->docente_id,
            'estado'      => 'activo',
        ]);

        return redirect()
            ->route('horarios.index')
            ->with('success', 'Horario registrado correctamente.');

        
    }

    public function destroy(Horario $horario)
    {
        $horario->delete();

        return redirect()
            ->route('horarios.index')
            ->with('success', 'Horario eliminado correctamente.');
    }
}