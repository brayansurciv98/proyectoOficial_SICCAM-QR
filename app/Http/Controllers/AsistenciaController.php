<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Estudiante;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Docente;
use Illuminate\Support\Facades\Auth;


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
            'estados.*' => ['nullable', 'in:' . implode(',', Asistencia::ESTADOS)],
        ]);

        $fecha = $request->fecha;
        $hora  = now()->format('H:i:s');
        $registrados = 0;

        foreach ($request->estados as $estudianteId => $estado) {
            if (!$estado || !Estudiante::whereKey($estudianteId)->exists()) {
                continue;
            }

            $existe = Asistencia::where('estudiante_id', $estudianteId)
                ->whereDate('fecha', $fecha)
                ->first();

            if ($existe) {
                $existe->update([
                    'estado'       => $estado,
                    'hora_ingreso' => $estado === Asistencia::AUSENTE ? null : ($existe->hora_ingreso ?? $hora),
                    'observacion'  => 'Registro manual',
                ]);
            } else {
                Asistencia::create([
                    'estudiante_id'  => $estudianteId,
                    'fecha'          => $fecha,
                    'hora_ingreso'   => $estado === Asistencia::AUSENTE ? null : $hora,
                    'estado'         => $estado,
                    'observacion'    => 'Registro manual',
                    'registrado_por' => 'Administrador',
                ]);
            }

            $registrados++;
        }

        return back()->with('success', "Se actualizaron {$registrados} registros de asistencia.");
    }


    public function manualDocente(Request $request)
{
    /** @var Docente $docente */
    $docente = Auth::guard('docente')->user();
    $docente->load('cursos');

    $cursosAsignados = $docente->cursos
        ->map(fn ($c) => [
            'curso' => (string) $c->curso,
            'paralelo' => $c->paralelo,
            'label' => $c->curso . '° ' . $c->paralelo,
        ])
        ->values();

    if ($request->filled('curso_paralelo') && str_contains($request->curso_paralelo, '|')) {
        [$curso, $paralelo] = explode('|', $request->curso_paralelo, 2);
    } else {
        $curso = $request->get('curso');
        $paralelo = $request->get('paralelo');
    }

    $fecha = $request->get('fecha', Carbon::today()->format('Y-m-d'));

    $estudiantes = collect();
    $asistenciasHoy = collect();

    $permitido = $cursosAsignados->contains(
        fn ($c) => $c['curso'] === (string) $curso && $c['paralelo'] === $paralelo
    );

    if ($curso && $paralelo && $permitido) {
        $estudiantes = Estudiante::where('curso', $curso)
            ->where('paralelo', $paralelo)
            ->where('estado', 'activo')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        $asistenciasHoy = Asistencia::whereDate('fecha', $fecha)
            ->whereIn('estudiante_id', $estudiantes->pluck('id'))
            ->get()
            ->keyBy('estudiante_id');
    }

    return view('docentes.asistencias_manual', compact(
        'docente',
        'cursosAsignados',
        'estudiantes',
        'curso',
        'paralelo',
        'fecha',
        'asistenciasHoy'
    ));
}

public function storeManualDocente(Request $request)
{
    /** @var Docente $docente */
    $docente = Auth::guard('docente')->user();
    $docente->load('cursos');

    $request->validate([
        'fecha' => ['required', 'date'],
        'curso' => ['required'],
        'paralelo' => ['required', 'in:A,B,C'],
        'asistencias' => ['required', 'array'],
        'asistencias.*.estado' => ['required', 'in:presente,tardanza,ausente'],
    ]);

    $curso = (string) $request->curso;
    $paralelo = $request->paralelo;

    $permitido = $docente->cursos->contains(
        fn ($c) => (string) $c->curso === $curso && $c->paralelo === $paralelo
    );

    if (!$permitido) {
        return back()->withErrors(['curso' => 'No tienes asignado ese curso/paralelo.']);
    }

    $fecha = $request->fecha;
    $hora = Carbon::now()->toTimeString();
    $registrados = 0;
    // La columna registrado_por es varchar(50)
    $registradoPor = mb_substr('Docente: ' . $docente->nombres . ' ' . $docente->apellidos, 0, 50);

    foreach ($request->asistencias as $estudianteId => $data) {
        $estudiante = Estudiante::where('id', $estudianteId)
            ->where('curso', $curso)
            ->where('paralelo', $paralelo)
            ->where('estado', 'activo')
            ->first();

        if (!$estudiante) {
            continue;
        }

        $estado = $data['estado'];

        $asistencia = Asistencia::where('estudiante_id', $estudiante->id)
            ->whereDate('fecha', $fecha)
            ->first();

        if ($asistencia) {
            $asistencia->update([
                'estado' => $estado,
                'hora_ingreso' => $estado === 'ausente' ? null : ($asistencia->hora_ingreso ?: $hora),
                'observacion' => 'Registro manual docente',
                'registrado_por' => $registradoPor,
            ]);
        } else {
            Asistencia::create([
                'estudiante_id' => $estudiante->id,
                'fecha' => $fecha,
                'hora_ingreso' => $estado === 'ausente' ? null : $hora,
                'estado' => $estado,
                'observacion' => 'Registro manual docente',
                'registrado_por' => $registradoPor,
            ]);
        }

        $registrados++;
    }

    return back()->with('success', "Se actualizaron {$registrados} registros de asistencia.");
}

}