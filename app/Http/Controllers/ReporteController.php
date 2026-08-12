<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Estudiante;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        // 1. Obtener rangos de fecha (por defecto: mes actual)
        $fechaDesde = $request->get('fecha_desde', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $fechaHasta = $request->get('fecha_hasta', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $cursoFiltro = $request->get('curso');
        $tipoReporte = $request->get('tipo_reporte', 'general');

        // 2. Consulta base de asistencias dentro del rango
        $queryAsistencias = Asistencia::whereBetween('fecha', [$fechaDesde, $fechaHasta]);

        if ($cursoFiltro) {
            $queryAsistencias->whereHas('estudiante', function ($q) use ($cursoFiltro) {
                $q->where('curso', $cursoFiltro);
            });
        }

        // 3. Métricas Globales
        $totalPresentes = (clone $queryAsistencias)->whereIn('estado', ['presente', 'a_tiempo'])->count();
        $totalTardanzas = (clone $queryAsistencias)->where('estado', 'atraso')->count();
        $totalAusencias = (clone $queryAsistencias)->where('estado', 'ausente')->count();

        $totalRegistros = $totalPresentes + $totalTardanzas + $totalAusencias;
        $porcentajeAsistencia = $totalRegistros > 0 
            ? round((($totalPresentes + $totalTardanzas) / $totalRegistros) * 100, 1) 
            : 0;

        // 4. Agrupación de datos para la tabla (Por Cursos)
        $cursos = ['1ro Secundaria', '2do Secundaria', '3ro Secundaria', '4to Secundaria', '5to Secundaria', '6to Secundaria'];
        if ($cursoFiltro) {
            $cursos = array_filter($cursos, fn($c) => $c === $cursoFiltro);
        }

        $reporteCursos = collect();

        foreach ($cursos as $curso) {
            $totalEstudiantesCurso = Estudiante::where('curso', $curso)->where('estado', 'activo')->count();
            
            $asistenciasCurso = Asistencia::whereBetween('fecha', [$fechaDesde, $fechaHasta])
                ->whereHas('estudiante', fn($q) => $q->where('curso', $curso));

            $presentes = (clone $asistenciasCurso)->whereIn('estado', ['presente', 'a_tiempo'])->count();
            $tardanzas = (clone $asistenciasCurso)->where('estado', 'atraso')->count();
            $ausencias = (clone $asistenciasCurso)->where('estado', 'ausente')->count();

            $totalEfectivo = $presentes + $tardanzas + $ausencias;
            $porcentaje = $totalEfectivo > 0 
                ? round((($presentes + $tardanzas) / $totalEfectivo) * 100, 1) 
                : 0;

            $reporteCursos->push([
                'curso' => $curso,
                'total_estudiantes' => $totalEstudiantesCurso,
                'presentes' => $presentes,
                'tardanzas' => $tardanzas,
                'ausencias' => $ausencias,
                'porcentaje' => $porcentaje,
            ]);
        }

        return view('reportes.index', compact(
            'fechaDesde',
            'fechaHasta',
            'cursoFiltro',
            'tipoReporte',
            'totalPresentes',
            'totalTardanzas',
            'totalAusencias',
            'porcentajeAsistencia',
            'reporteCursos'
        ));
    }
}