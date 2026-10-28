<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de asistencia</title>
    <style>
        @page { margin: 28px 32px 45px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }

        .encabezado { width: 100%; border-bottom: 3px solid #1a5f2a; padding-bottom: 8px; margin-bottom: 12px; }
        .encabezado td { vertical-align: middle; }
        .titulo { color: #1a5f2a; font-size: 17px; font-weight: bold; margin: 0; }
        .sub { color: #555; margin: 2px 0; }

        .resumen { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-bottom: 14px; }
        .resumen td { text-align: center; color: #fff; padding: 8px; }
        .resumen .num { font-size: 18px; font-weight: bold; display: block; }
        .resumen td.verde { background: #28a745; }
        .resumen td.amarillo { background: #ffc107; color: #212529; }
        .resumen td.rojo { background: #dc3545; }
        .resumen td.azul { background: #1a5f2a; }

        table.datos { width: 100%; border-collapse: collapse; }
        table.datos th { background: #1a5f2a; color: #fff; padding: 6px; font-size: 10px; text-align: left; }
        table.datos td { padding: 5px 6px; border-bottom: 1px solid #ddd; }
        table.datos tr:nth-child(even) td { background: #f4f6f9; }
        .c { text-align: center; }

        .Puntual { color: #1e7e34; font-weight: bold; }
        .Impuntual { color: #b8860b; font-weight: bold; }
        .Irregular { color: #c82333; font-weight: bold; }

        .nota { font-size: 9px; color: #666; margin-top: 8px; }
        .pie { position: fixed; bottom: -30px; left: 0; right: 0; text-align: center; font-size: 9px; color: #888; }
    </style>
</head>
<body>

<div class="pie">SICCAM-QR · Sistema de Control de Asistencia · Generado el {{ $generado }}</div>

<table class="encabezado">
    <tr>
        <td style="width: 70px;">
            @if($logo)
                <img src="{{ $logo }}" style="width: 60px;">
            @endif
        </td>
        <td>
            <p class="titulo">Reporte de asistencia — {{ $tiposReporte[$tipoReporte] }}</p>
            <p class="sub">{{ \App\Models\Configuracion::colegio() }}@if(\App\Models\Configuracion::valor('codigo_sie')) · SIE {{ \App\Models\Configuracion::valor('codigo_sie') }}@endif · Turno tarde</p>
            <p class="sub">
                Del {{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }}
                al {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }} ·
                Curso: {{ $cursoFiltro ? $listaCursos[$cursoFiltro] : 'Todos' }}
            </p>
        </td>
    </tr>
</table>

<table class="resumen">
    <tr>
        <td class="verde"><span class="num">{{ $totalPresentes }}</span>Presentes</td>
        <td class="amarillo"><span class="num">{{ $totalTardanzas }}</span>Tardanzas</td>
        <td class="rojo"><span class="num">{{ $totalAusencias }}</span>Ausencias</td>
        <td class="azul"><span class="num">{{ $porcentajeAsistencia }}%</span>Asistencia</td>
    </tr>
</table>

@if($tipoReporte === 'estudiante')
    <table class="datos">
        <thead>
            <tr>
                <th>#</th>
                <th>Código</th>
                <th>Estudiante</th>
                <th>Curso</th>
                <th class="c">Pres.</th>
                <th class="c">Tard.</th>
                <th class="c">Aus.</th>
                <th class="c">%</th>
                <th>Observación</th>
            </tr>
        </thead>
        <tbody>
            @forelse($porEstudiante as $i => $e)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $e['codigo'] }}</td>
                    <td>{{ $e['nombre'] }}</td>
                    <td>{{ $e['curso'] }}</td>
                    <td class="c">{{ $e['presentes'] }}</td>
                    <td class="c">{{ $e['tardanzas'] }}</td>
                    <td class="c">{{ $e['ausencias'] }}</td>
                    <td class="c">{{ $e['porcentaje'] }}%</td>
                    <td class="{{ $e['observacion'] }}">{{ $e['observacion'] }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="c">No hay estudiantes para este filtro.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="nota">
        Observación automática: <b>Irregular</b> = 20 % o más de ausencias ·
        <b>Impuntual</b> = 20 % o más de tardanzas · <b>Puntual</b> = el resto.
    </p>

@elseif(in_array($tipoReporte, ['tardanzas', 'ausencias']))
    <table class="datos">
        <thead>
            <tr>
                <th>#</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Código</th>
                <th>Estudiante</th>
                <th>Curso</th>
                <th>Registrado por</th>
            </tr>
        </thead>
        <tbody>
            @forelse($registros as $i => $r)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($r->fecha)->format('d/m/Y') }}</td>
                    <td>{{ $r->hora_ingreso ? substr($r->hora_ingreso, 0, 5) : '—' }}</td>
                    <td>{{ $r->estudiante->codigo_estudiante ?? '' }}</td>
                    <td>{{ $r->estudiante->apellidos ?? '' }} {{ $r->estudiante->nombres ?? '' }}</td>
                    <td>{{ $r->estudiante->curso ?? '' }}° {{ $r->estudiante->paralelo ?? '' }}</td>
                    <td>{{ $r->registrado_por }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="c">No hay registros en este periodo.</td></tr>
            @endforelse
        </tbody>
    </table>

@else
    <table class="datos">
        <thead>
            <tr>
                <th>Curso</th>
                <th class="c">Estudiantes activos</th>
                <th class="c">Presentes</th>
                <th class="c">Tardanzas</th>
                <th class="c">Ausencias</th>
                <th class="c">% Asistencia</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reporteCursos as $fila)
                <tr>
                    <td><b>{{ $fila['curso'] }}</b></td>
                    <td class="c">{{ $fila['total_estudiantes'] }}</td>
                    <td class="c">{{ $fila['presentes'] }}</td>
                    <td class="c">{{ $fila['tardanzas'] }}</td>
                    <td class="c">{{ $fila['ausencias'] }}</td>
                    <td class="c"><b>{{ $fila['porcentaje'] }}%</b></td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

</body>
</html>