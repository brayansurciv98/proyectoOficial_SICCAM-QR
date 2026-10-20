<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\AsistenciaClase;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Horario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Asistencia del docente en su clase (por bloque del horario), solo del día de hoy.
 */
class AsistenciaClaseController extends Controller
{
    // GET /docente/clases  ->  Mis clases de hoy
        // GET /docente/clases  ->  Mi horario: clases de hoy + horario semanal
    public function index()
    {
        /** @var Docente $docente */
        $docente = Auth::guard('docente')->user();
        $docente->load('cursos');

        $clases = Horario::jornadaDe($docente);

        // Horario semanal completo: $grilla[bloque][dia] = [clases...]
        $semana = $docente->horarios()
            ->with('materia')
            ->where('estado', 'activo')
            ->orderBy('dia')
            ->orderBy('bloque')
            ->get();

        $grilla = [];
        foreach ($semana as $h) {
            $grilla[$h->bloque][$h->dia][] = $h;
        }

        // Cursos asignados que todavía no tienen clases en Horarios
        $sinHorario = $docente->cursos->reject(fn ($c) => $semana->contains(
            fn ($h) => (string) $h->curso === (string) $c->curso && $h->paralelo === $c->paralelo
        ))->values();

        return view('docentes.clases_hoy', compact('docente', 'clases', 'grilla', 'sinHorario'));
    }

    // GET /docente/clases/{horario}/asistencia?filtro=faltantes
    public function create(Request $request, Horario $horario)
    {
        /** @var Docente $docente */
        $docente = Auth::guard('docente')->user();
        $this->autorizar($horario, $docente);

        $hoy = today()->toDateString();
        $filtro = $request->get('filtro') === 'faltantes' ? 'faltantes' : 'todos';

        $estudiantes = Estudiante::where('curso', $horario->curso)
            ->where('paralelo', $horario->paralelo)
            ->where('estado', 'activo')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        // Ingreso al colegio de hoy (QR o registro manual)
        $ingresos = Asistencia::whereDate('fecha', $hoy)
            ->whereIn('estudiante_id', $estudiantes->pluck('id'))
            ->get()
            ->keyBy('estudiante_id');

        // Lo que ya guardó en esta clase hoy
        $marcados = AsistenciaClase::where('horario_id', $horario->id)
            ->whereDate('fecha', $hoy)
            ->get()
            ->keyBy('estudiante_id');

        // Solo faltantes: sin ingreso al colegio hoy o marcados como ausentes
        if ($filtro === 'faltantes') {
            $estudiantes = $estudiantes->filter(function ($estudiante) use ($ingresos) {
                $ingreso = $ingresos->get($estudiante->id);
                return !$ingreso || $ingreso->estado === Asistencia::AUSENTE;
            })->values();
        }

        $horario->load('materia');

        return view('docentes.clase_asistencia', compact(
            'docente', 'horario', 'estudiantes', 'ingresos', 'marcados', 'filtro', 'hoy'
        ));
    }

    // POST /docente/clases/{horario}/asistencia
    public function store(Request $request, Horario $horario)
    {
        /** @var Docente $docente */
        $docente = Auth::guard('docente')->user();
        $this->autorizar($horario, $docente);

        $request->validate([
            'asistencias'   => ['required', 'array'],
            'asistencias.*' => ['required', 'in:' . implode(',', Asistencia::ESTADOS)],
        ]);

        $hoy = today()->toDateString();

        // Solo se aceptan estudiantes de ese curso y paralelo
        $idsDelCurso = Estudiante::where('curso', $horario->curso)
            ->where('paralelo', $horario->paralelo)
            ->where('estado', 'activo')
            ->pluck('id')
            ->all();

        $guardados = 0;

        foreach ($request->asistencias as $estudianteId => $estado) {
            if (!in_array((int) $estudianteId, $idsDelCurso, true)) {
                continue;
            }

            AsistenciaClase::updateOrCreate(
                ['horario_id' => $horario->id, 'estudiante_id' => (int) $estudianteId, 'fecha' => $hoy],
                ['docente_id' => $docente->id, 'estado' => $estado]
            );
            $guardados++;
        }

        return redirect()
            ->route('docente.clases.asistencia', [
                'horario' => $horario->id,
                'filtro'  => $request->input('filtro', 'todos'),
            ])
            ->with('success', "Asistencia de la clase guardada ({$guardados} estudiante(s)).");
    }

    /**
     * Solo su propia clase y solo el día que le toca (hoy).
     */
    private function autorizar(Horario $horario, Docente $docente): void
    {
        abort_unless((int) $horario->docente_id === (int) $docente->id, 403, 'Esta clase no es suya.');
        abort_unless((int) $horario->dia === now()->dayOfWeekIso, 403, 'Solo puede tomar asistencia de las clases de hoy.');
    }
}