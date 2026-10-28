<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\AsistenciaClase;
use App\Models\Comunicado;
use App\Models\Configuracion;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Notificacion;
use App\Models\Tutor;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Resultados del funcionamiento del sistema en un periodo (por ejemplo, la prueba piloto):
 * cuántos ingresos registró el lector QR, en cuánto tiempo, qué avisos llegaron, etc.
 */
class ResultadosController extends Controller
{
    // Una lectura que tardó más que esto en guardarse se envió después (sin conexión)
    private const SEGUNDOS_EN_LINEA = 60;

    public function index(Request $request)
    {
        return view('reportes.resultados', $this->metricas($request));
    }

    public function pdf(Request $request)
    {
        $datos = $this->metricas($request);

        return Pdf::loadView('reportes.resultados_pdf', $datos)
            ->setPaper('letter')
            ->stream('Resultados_SICCAM-QR_' . $datos['desde'] . '_' . $datos['hasta'] . '.pdf');
    }

    private function metricas(Request $request): array
    {
        $desde = $this->fecha($request->get('desde'));
        $hasta = $this->fecha($request->get('hasta'));
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        $finDia = $hasta . ' 23:59:59';

        $asistencias = Asistencia::whereDate('fecha', '>=', $desde)->whereDate('fecha', '<=', $hasta)->get();
        $dias = $asistencias->map(fn ($a) => Carbon::parse($a->fecha)->toDateString())->unique()->count();
        $activos = Estudiante::where('estado', 'activo')->count();

        // Origen de cada registro de ingreso
        $origen = [
            'Lector QR (Raspberry Pi)' => $asistencias->where('registrado_por', 'QR_Raspberry')->count(),
            'Registro manual del administrador' => $asistencias->where('registrado_por', 'Administrador')->count(),
            'Registro manual del docente' => $asistencias->filter(fn ($a) => str_starts_with((string) $a->registrado_por, 'Docente'))->count(),
            'Cierre automático (ausentes)' => $asistencias->where('registrado_por', 'Cierre automatico')->count(),
        ];
        $origen['Otros'] = $asistencias->count() - array_sum($origen);

        // Lector QR: tiempo entre la lectura (hora de la Raspberry) y el registro en el servidor
        $demoras = $asistencias
            ->where('registrado_por', 'QR_Raspberry')
            ->filter(fn ($a) => $a->hora_ingreso)
            ->map(function ($a) {
                $lectura = Carbon::parse(Carbon::parse($a->fecha)->toDateString() . ' ' . $a->hora_ingreso);
                return max(0, $lectura->diffInSeconds($a->updated_at, false));
            });
        $enLinea = $demoras->filter(fn ($s) => $s <= self::SEGUNDOS_EN_LINEA);

        $notificaciones = Notificacion::whereDate('fecha', '>=', $desde)->whereDate('fecha', '<=', $hasta)->get();
        $tiposAviso = ['ausencia' => 'Inasistencia', 'tardanza' => 'Llegada con retraso', 'comunicado' => 'Comunicado', 'examen' => 'Examen programado'];

        $ingresosPorDia = $asistencias
            ->whereIn('estado', [Asistencia::PRESENTE, Asistencia::TARDANZA])
            ->groupBy(fn ($a) => Carbon::parse($a->fecha)->toDateString())
            ->map->count();

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'colegio' => Configuracion::colegio(),
            'dias' => $dias,
            'activos' => $activos,
            'docentes' => Docente::where('estado', 'activo')->count(),
            'tutores' => Tutor::where('estado', 'activo')->count(),
            'tutoresConApp' => Tutor::where('estado', 'activo')->whereNotNull('fcm_token')->count(),
            'totalRegistros' => $asistencias->count(),
            'presentes' => $asistencias->where('estado', Asistencia::PRESENTE)->count(),
            'tardanzas' => $asistencias->where('estado', Asistencia::TARDANZA)->count(),
            'ausentes' => $asistencias->where('estado', Asistencia::AUSENTE)->count(),
            'origen' => $origen,
            'lecturasQr' => $demoras->count(),
            'lecturasEnLinea' => $enLinea->count(),
            'lecturasSincronizadas' => $demoras->count() - $enLinea->count(),
            'segundosPromedio' => $enLinea->count() ? round($enLinea->avg(), 1) : null,
            'segundosMaximo' => $enLinea->count() ? $enLinea->max() : null,
            'coberturaDiaria' => $activos && $ingresosPorDia->count()
                ? round($ingresosPorDia->avg() / $activos * 100, 1) : null,
            'avisosPorTipo' => collect($tiposAviso)->mapWithKeys(fn ($nombre, $tipo) => [
                $nombre => $notificaciones->where('tipo', $tipo)->count(),
            ])->all(),
            'avisos' => $notificaciones->count(),
            'avisosPush' => $notificaciones->where('enviada_push', true)->count(),
            'avisosLeidos' => $notificaciones->whereNotNull('leida_en')->count(),
            'comunicados' => Comunicado::where('estado', 'enviado')->whereBetween('created_at', [$desde, $finDia])->count(),
            'examenes' => Examen::whereBetween('created_at', [$desde, $finDia])->count(),
            'asistenciaClase' => AsistenciaClase::whereDate('fecha', '>=', $desde)->whereDate('fecha', '<=', $hasta)->count(),
            'clasesConAsistencia' => AsistenciaClase::whereDate('fecha', '>=', $desde)->whereDate('fecha', '<=', $hasta)
                ->get()->map(fn ($a) => $a->horario_id . '|' . Carbon::parse($a->fecha)->toDateString())->unique()->count(),
            'generado' => now()->format('d/m/Y H:i'),
        ];
    }

    private function fecha(?string $fecha): string
    {
        $valida = $fecha ? \DateTime::createFromFormat('!Y-m-d', $fecha) : false;

        return ($valida && $valida->format('Y-m-d') === $fecha) ? $fecha : today()->toDateString();
    }
}
