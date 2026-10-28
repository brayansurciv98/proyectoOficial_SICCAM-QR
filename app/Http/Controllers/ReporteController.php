<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Configuracion;
use App\Models\Estudiante;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReporteController extends Controller
{
    // En la BD el curso se guarda como número (1 a 6)
    public const CURSOS = [
        '1' => '1ro Secundaria',
        '2' => '2do Secundaria',
        '3' => '3ro Secundaria',
        '4' => '4to Secundaria',
        '5' => '5to Secundaria',
        '6' => '6to Secundaria',
    ];

    public const TIPOS = [
        'general'    => 'Resumen general',
        'estudiante' => 'Por estudiante',
        'curso'      => 'Por curso',
        'tardanzas'  => 'Tardanzas',
        'ausencias'  => 'Ausencias',
    ];

    public function index(Request $request)
    {
        return view('reportes.index', $this->datos($request));
    }

    // GET /reportes/pdf  (mismos filtros que la pantalla)
    public function pdf(Request $request)
    {
        $datos = $this->datos($request);
        $datos['generado'] = now()->format('d/m/Y H:i');

        $logo = public_path('images/Rene_Barrientos_Ortuno.jpg');
        $datos['logo'] = is_file($logo)
            ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logo))
            : null;

        return Pdf::loadView('reportes.pdf', $datos)
            ->setPaper('a4', 'portrait')
            ->download('Reporte_asistencia_' . $datos['fechaDesde'] . '_al_' . $datos['fechaHasta'] . '.pdf');
    }

    // GET /reportes/excel  ->  CSV que se abre directo en Excel
        // GET /reportes/excel  ->  archivo .xlsx con diseño (logo, colores, filtros)
    public function excel(Request $request)
    {
        $d = $this->datos($request);
        [$columnas, $filas] = $this->tablaExcel($d);

        $totalColumnas = max(count($columnas), 8); // las tarjetas de resumen ocupan 8 columnas
        $ultimaCol = Coordinate::stringFromColumnIndex($totalColumnas);
        $ultimaColTabla = Coordinate::stringFromColumnIndex(count($columnas));

        $libro = new Spreadsheet();
        $libro->getProperties()->setCreator('SICCAM-QR')->setTitle('Reporte de asistencia');
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Reporte');
        $hoja->setShowGridlines(false);

        // 1. Logo y títulos
        $logo = public_path('images/Rene_Barrientos_Ortuno.jpg');
        if (is_file($logo)) {
            $dibujo = new Drawing();
            $dibujo->setPath($logo);
            $dibujo->setHeight(62);
            $dibujo->setCoordinates('A1');
            $dibujo->setOffsetX(10);
            $dibujo->setOffsetY(3);
            $dibujo->setWorksheet($hoja);
        }

        $periodo = Carbon::parse($d['fechaDesde'])->format('d/m/Y') . ' al ' . Carbon::parse($d['fechaHasta'])->format('d/m/Y');
        $curso = $d['cursoFiltro'] ? self::CURSOS[$d['cursoFiltro']] : 'Todos los cursos';

        $titulos = [
            1 => ['REPORTE DE ASISTENCIA — ' . mb_strtoupper(self::TIPOS[$d['tipoReporte']], 'UTF-8'), 15, true, '1A5F2A'],
            2 => [Configuracion::colegio() . ' · Turno tarde · SICCAM-QR', 11, false, '555555'],
            3 => ['Periodo: ' . $periodo . '     |     Curso: ' . $curso, 10, false, '555555'],
        ];
        foreach ($titulos as $fila => [$texto, $tamano, $negrita, $color]) {
            $hoja->mergeCells("B{$fila}:{$ultimaCol}{$fila}");
            $hoja->setCellValue("B{$fila}", $texto);
            $hoja->getStyle("B{$fila}")->getFont()->setSize($tamano)->setBold($negrita)->getColor()->setRGB($color);
        }
        $hoja->getRowDimension(1)->setRowHeight(24);
        $hoja->getStyle("A4:{$ultimaCol}4")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_THICK)->getColor()->setRGB('1A5F2A');

        // 2. Tarjetas de resumen (fila 6: título, fila 7: número)
        $tarjetas = [
            ['A', 'B', 'PRESENTES', $d['totalPresentes'], '28A745', 'FFFFFF'],
            ['C', 'D', 'TARDANZAS', $d['totalTardanzas'], 'FFC107', '212529'],
            ['E', 'F', 'AUSENCIAS', $d['totalAusencias'], 'DC3545', 'FFFFFF'],
            ['G', 'H', '% ASISTENCIA', $d['porcentajeAsistencia'] / 100, '1A5F2A', 'FFFFFF'],
        ];
        foreach ($tarjetas as [$c1, $c2, $etiqueta, $valor, $fondo, $letra]) {
            $hoja->mergeCells("{$c1}6:{$c2}6");
            $hoja->mergeCells("{$c1}7:{$c2}7");
            $hoja->setCellValue("{$c1}6", $etiqueta);
            $hoja->setCellValue("{$c1}7", $valor);

            $estilo = $hoja->getStyle("{$c1}6:{$c2}7");
            $estilo->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($fondo);
            $estilo->getFont()->setBold(true)->getColor()->setRGB($letra);
            $estilo->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $hoja->getStyle("{$c1}7")->getFont()->setSize(16);
        }
        $hoja->getStyle('G7')->getNumberFormat()->setFormatCode('0.0%');
        $hoja->getRowDimension(7)->setRowHeight(28);

        // 3. Encabezado de la tabla
        $filaEncabezado = 9;
        foreach ($columnas as $i => $columna) {
            $letra = Coordinate::stringFromColumnIndex($i + 1);
            $hoja->setCellValue("{$letra}{$filaEncabezado}", $columna[0]);
            $hoja->getColumnDimension($letra)->setWidth($columna[1]);
        }
        // Si la tabla tiene menos de 8 columnas, igual se ven bien las tarjetas
        for ($i = count($columnas) + 1; $i <= $totalColumnas; $i++) {
            $hoja->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(13);
        }

        $hoja->getStyle("A{$filaEncabezado}:{$ultimaColTabla}{$filaEncabezado}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A5F2A']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
        ]);
        $hoja->getRowDimension($filaEncabezado)->setRowHeight(24);

        // 4. Filas de datos (alternadas en gris)
        $fila = $filaEncabezado + 1;
        foreach ($filas as $n => $valores) {
            foreach (array_values($valores) as $i => $valor) {
                $hoja->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . $fila, $valor);
            }
            if ($n % 2 === 1) {
                $hoja->getStyle("A{$fila}:{$ultimaColTabla}{$fila}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F4F6F9');
            }
            $fila++;
        }

        if (empty($filas)) {
            $hoja->mergeCells("A{$fila}:{$ultimaColTabla}{$fila}");
            $hoja->setCellValue("A{$fila}", 'No hay datos para este filtro.');
            $hoja->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $fila++;
        }
        $ultimaFila = $fila - 1;
        $primeraFilaDatos = $filaEncabezado + 1;

        // Bordes de toda la tabla
        $hoja->getStyle("A{$filaEncabezado}:{$ultimaColTabla}{$ultimaFila}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D0D7DE');

        // 5. Formato por columna: centrado, porcentaje y color de la observación
        foreach ($columnas as $i => $columna) {
            $letra = Coordinate::stringFromColumnIndex($i + 1);
            $tipo = $columna[2] ?? 'texto';
            $rango = "{$letra}{$primeraFilaDatos}:{$letra}{$ultimaFila}";

            if (in_array($tipo, ['centro', 'numero', 'porcentaje', 'observacion'], true)) {
                $hoja->getStyle($rango)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            if ($tipo === 'porcentaje') {
                $hoja->getStyle($rango)->getNumberFormat()->setFormatCode('0.0%');
            }
            if ($tipo === 'observacion') {
                $colores = ['Puntual' => '1E7E34', 'Impuntual' => 'B8860B', 'Irregular' => 'C82333'];
                for ($r = $primeraFilaDatos; $r <= $ultimaFila; $r++) {
                    $valor = (string) $hoja->getCell("{$letra}{$r}")->getValue();
                    $hoja->getStyle("{$letra}{$r}")->getFont()->setBold(true)
                        ->getColor()->setRGB($colores[$valor] ?? '6C757D');
                }
            }
        }

        // 6. Filtros, encabezado fijo al bajar y pie
        if (!empty($filas)) {
            $hoja->setAutoFilter("A{$filaEncabezado}:{$ultimaColTabla}{$ultimaFila}");
        }
        $hoja->freezePane('A' . $primeraFilaDatos);

        $filaPie = $ultimaFila + 2;
        if ($d['tipoReporte'] === 'estudiante') {
            $hoja->setCellValue("A{$filaPie}", 'Observación automática: Irregular = 20 % o más de ausencias · Impuntual = 20 % o más de tardanzas · Puntual = el resto.');
            $hoja->getStyle("A{$filaPie}")->getFont()->setSize(9)->getColor()->setRGB('555555');
            $filaPie++;
        }
        $hoja->setCellValue("A{$filaPie}", 'Generado el ' . now()->format('d/m/Y H:i') . ' · SICCAM-QR');
        $hoja->getStyle("A{$filaPie}")->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('888888');

        // 7. Impresión: horizontal, ajustado al ancho y con el encabezado en cada página
        $hoja->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setRowsToRepeatAtTopByStartAndEnd($filaEncabezado, $filaEncabezado);

        $nombreArchivo = 'Reporte_asistencia_' . $d['fechaDesde'] . '_al_' . $d['fechaHasta'] . '.xlsx';

        return response()->streamDownload(function () use ($libro) {
            (new Xlsx($libro))->save('php://output');
        }, $nombreArchivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Columnas ([título, ancho, tipo]) y filas de la tabla del Excel según el tipo de reporte.
     */
    private function tablaExcel(array $d): array
    {
        if ($d['tipoReporte'] === 'estudiante') {
            $columnas = [
                ['Código', 16, 'centro'],
                ['Estudiante', 34],
                ['Curso', 9, 'centro'],
                ['Presentes', 11, 'numero'],
                ['Tardanzas', 11, 'numero'],
                ['Ausencias', 11, 'numero'],
                ['% Asistencia', 13, 'porcentaje'],
                ['Observación', 15, 'observacion'],
            ];
            $filas = $d['porEstudiante']->map(fn ($e) => [
                $e['codigo'], $e['nombre'], $e['curso'], $e['presentes'], $e['tardanzas'],
                $e['ausencias'], $e['porcentaje'] / 100, $e['observacion'],
            ])->all();

            return [$columnas, $filas];
        }

        if (in_array($d['tipoReporte'], ['tardanzas', 'ausencias'], true)) {
            $columnas = [
                ['Fecha', 13, 'centro'],
                ['Hora', 9, 'centro'],
                ['Código', 16, 'centro'],
                ['Estudiante', 34],
                ['Curso', 9, 'centro'],
                ['Registrado por', 28],
            ];
            $filas = $d['registros']->map(fn ($r) => [
                Carbon::parse($r->fecha)->format('d/m/Y'),
                $r->hora_ingreso ? substr($r->hora_ingreso, 0, 5) : '—',
                $r->estudiante->codigo_estudiante ?? '',
                trim(($r->estudiante->apellidos ?? '') . ' ' . ($r->estudiante->nombres ?? '')),
                ($r->estudiante->curso ?? '') . '° ' . ($r->estudiante->paralelo ?? ''),
                $r->registrado_por,
            ])->all();

            return [$columnas, $filas];
        }

        // Resumen general / por curso
        $columnas = [
            ['Curso', 20],
            ['Estudiantes activos', 13, 'numero'],
            ['Presentes', 11, 'numero'],
            ['Tardanzas', 11, 'numero'],
            ['Ausencias', 11, 'numero'],
            ['% Asistencia', 13, 'porcentaje'],
        ];
        $filas = $d['reporteCursos']->map(fn ($c) => [
            $c['curso'], $c['total_estudiantes'], $c['presentes'], $c['tardanzas'],
            $c['ausencias'], $c['porcentaje'] / 100,
        ])->all();

        return [$columnas, $filas];
    }

    /**
     * Calcula todo lo que muestran la pantalla, el PDF y el Excel.
     */
    private function fechaValida(?string $fecha, Carbon $defecto): string
    {
        $valida = $fecha ? \DateTime::createFromFormat('!Y-m-d', $fecha) : false;

        return ($valida && $valida->format('Y-m-d') === $fecha) ? $fecha : $defecto->format('Y-m-d');
    }

    private function datos(Request $request): array
    {
        // 1. Filtros (por defecto: mes actual, todos los cursos, resumen general).
        // Una fecha mal escrita en la dirección se reemplaza por la de defecto en vez de dar error.
        $fechaDesde = $this->fechaValida($request->get('fecha_desde'), Carbon::now()->startOfMonth());
        $fechaHasta = $this->fechaValida($request->get('fecha_hasta'), Carbon::now()->endOfMonth());
        $cursoFiltro = $request->get('curso');
        $tipoReporte = $request->get('tipo_reporte', 'general');

        if (!array_key_exists($tipoReporte, self::TIPOS)) {
            $tipoReporte = 'general';
        }
        if ($cursoFiltro !== null && !array_key_exists($cursoFiltro, self::CURSOS)) {
            $cursoFiltro = null;
        }
        if ($fechaDesde > $fechaHasta) {
            [$fechaDesde, $fechaHasta] = [$fechaHasta, $fechaDesde];
        }

        // 2. Asistencias del rango (y del curso, si se eligió)
        $base = Asistencia::whereBetween('fecha', [$fechaDesde, $fechaHasta]);
        if ($cursoFiltro) {
            $base->whereHas('estudiante', fn ($q) => $q->where('curso', $cursoFiltro));
        }

        // 3. Métricas globales
        $totalPresentes = (clone $base)->where('estado', Asistencia::PRESENTE)->count();
        $totalTardanzas = (clone $base)->where('estado', Asistencia::TARDANZA)->count();
        $totalAusencias = (clone $base)->where('estado', Asistencia::AUSENTE)->count();

        $totalRegistros = $totalPresentes + $totalTardanzas + $totalAusencias;
        $porcentajeAsistencia = $totalRegistros > 0
            ? round((($totalPresentes + $totalTardanzas) / $totalRegistros) * 100, 1)
            : 0;

        // 4. Tabla por curso
        $cursos = $cursoFiltro ? [$cursoFiltro => self::CURSOS[$cursoFiltro]] : self::CURSOS;
        $reporteCursos = collect();

        foreach ($cursos as $curso => $nombreCurso) {
            $asistenciasCurso = Asistencia::whereBetween('fecha', [$fechaDesde, $fechaHasta])
                ->whereHas('estudiante', fn ($q) => $q->where('curso', $curso));

            $presentes = (clone $asistenciasCurso)->where('estado', Asistencia::PRESENTE)->count();
            $tardanzas = (clone $asistenciasCurso)->where('estado', Asistencia::TARDANZA)->count();
            $ausencias = (clone $asistenciasCurso)->where('estado', Asistencia::AUSENTE)->count();
            $total = $presentes + $tardanzas + $ausencias;

            $reporteCursos->push([
                'curso'             => $nombreCurso,
                'total_estudiantes' => Estudiante::where('curso', $curso)->where('estado', 'activo')->count(),
                'presentes'         => $presentes,
                'tardanzas'         => $tardanzas,
                'ausencias'         => $ausencias,
                'porcentaje'        => $total > 0 ? round((($presentes + $tardanzas) / $total) * 100, 1) : 0,
            ]);
        }

        // 5. Por estudiante (con observación automática)
        $porEstudiante = collect();
        if ($tipoReporte === 'estudiante') {
            $conteos = (clone $base)
                ->selectRaw('estudiante_id, estado, COUNT(*) as total')
                ->groupBy('estudiante_id', 'estado')
                ->get()
                ->groupBy('estudiante_id');

            $porEstudiante = Estudiante::where('estado', 'activo')
                ->when($cursoFiltro, fn ($q) => $q->where('curso', $cursoFiltro))
                ->orderBy('curso')
                ->orderBy('paralelo')
                ->orderBy('apellidos')
                ->get()
                ->map(function ($e) use ($conteos) {
                    $c = $conteos->get($e->id, collect())->pluck('total', 'estado');
                    $p = (int) ($c[Asistencia::PRESENTE] ?? 0);
                    $t = (int) ($c[Asistencia::TARDANZA] ?? 0);
                    $a = (int) ($c[Asistencia::AUSENTE] ?? 0);
                    $total = $p + $t + $a;

                    return [
                        'codigo'      => $e->codigo_estudiante,
                        'nombre'      => $e->apellidos . ' ' . $e->nombres,
                        'curso'       => $e->curso . '° ' . $e->paralelo,
                        'presentes'   => $p,
                        'tardanzas'   => $t,
                        'ausencias'   => $a,
                        'porcentaje'  => $total > 0 ? round((($p + $t) / $total) * 100, 1) : 0,
                        'observacion' => self::observacion($p, $t, $a),
                    ];
                });
        }

        // 6. Lista de tardanzas o ausencias
        $registros = collect();
        if (in_array($tipoReporte, ['tardanzas', 'ausencias'], true)) {
            $registros = (clone $base)
                ->with('estudiante')
                ->where('estado', $tipoReporte === 'tardanzas' ? Asistencia::TARDANZA : Asistencia::AUSENTE)
                ->orderBy('fecha', 'desc')
                ->orderBy('hora_ingreso')
                ->get();
        }

        $listaCursos = self::CURSOS;
        $tiposReporte = self::TIPOS;

        return compact(
            'fechaDesde', 'fechaHasta', 'cursoFiltro', 'tipoReporte',
            'totalPresentes', 'totalTardanzas', 'totalAusencias', 'porcentajeAsistencia',
            'reporteCursos', 'porEstudiante', 'registros', 'listaCursos', 'tiposReporte'
        );
    }

    /**
     * Observación automática según el comportamiento del estudiante:
     * Irregular = 20 % o más de ausencias; Impuntual = 20 % o más de tardanzas.
     */
    public static function observacion(int $presentes, int $tardanzas, int $ausencias): string
    {
        $total = $presentes + $tardanzas + $ausencias;

        if ($total === 0) {
            return 'Sin registros';
        }
        if ($ausencias / $total >= 0.2) {
            return 'Irregular';
        }
        if ($tardanzas / $total >= 0.2) {
            return 'Impuntual';
        }
        return 'Puntual';
    }
}