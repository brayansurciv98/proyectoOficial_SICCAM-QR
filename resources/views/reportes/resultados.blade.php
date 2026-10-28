@extends('layouts.app')

@section('title', 'Resultados del sistema - SICCAM QR')
@section('page_title', 'Resultados del sistema')

@section('content')
@php
    $periodo = \Carbon\Carbon::parse($desde)->format('d/m/Y') . ($desde !== $hasta ? ' al ' . \Carbon\Carbon::parse($hasta)->format('d/m/Y') : '');
    $porcentaje = fn ($parte, $total) => $total ? round($parte / $total * 100, 1) . ' %' : '—';
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div class="mr-3">
        <h5 class="mb-1 font-weight-bold text-dark">Resultados del funcionamiento</h5>
        <small class="text-muted">Lo que registró el sistema en el periodo elegido (por ejemplo, el día de la prueba piloto).</small>
    </div>
    <a href="{{ route('reportes.index') }}" class="btn btn-outline-secondary btn-sm mt-2 mt-md-0">
        <i class="bi bi-arrow-left mr-1"></i> Reportes de asistencia
    </a>
</div>

<div class="card card-outline card-success shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reportes.resultados') }}" class="row align-items-end">
            <div class="col-md-3 form-group mb-md-0">
                <label class="small text-muted font-weight-bold">Desde</label>
                <input type="date" name="desde" class="form-control" value="{{ $desde }}">
            </div>
            <div class="col-md-3 form-group mb-md-0">
                <label class="small text-muted font-weight-bold">Hasta</label>
                <input type="date" name="hasta" class="form-control" value="{{ $hasta }}">
            </div>
            <div class="col-md-6 d-flex">
                <button class="btn btn-success mr-2"><i class="bi bi-search mr-1"></i> Consultar</button>
                <a href="{{ route('reportes.resultados.pdf', ['desde' => $desde, 'hasta' => $hasta]) }}" target="_blank" class="btn btn-outline-danger">
                    <i class="bi bi-file-earmark-pdf mr-1"></i> Informe en PDF
                </a>
            </div>
        </form>
    </div>
</div>

<div class="row">
    @foreach([
        ['bi-calendar-check', 'bg-success', 'Días con registros', $dias],
        ['bi-qr-code-scan', 'bg-primary', 'Lecturas del lector QR', $lecturasQr],
        ['bi-stopwatch', 'bg-info', 'Tiempo promedio de registro', $segundosPromedio !== null ? $segundosPromedio . ' s' : '—'],
        ['bi-bell', 'bg-warning', 'Avisos enviados por push', $avisosPush . ' de ' . $avisos],
    ] as [$icono, $color, $texto, $valor])
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="info-box shadow-sm mb-3">
                <span class="info-box-icon {{ $color }} elevation-1 text-white"><i class="bi {{ $icono }}"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ $texto }}</span>
                    <span class="info-box-number h4 mb-0 font-weight-bold">{{ $valor }}</span>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card card-outline card-success shadow-sm mb-4">
            <div class="card-header"><strong><i class="bi bi-door-open mr-1"></i> Control de ingreso · {{ $periodo }}</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr><td>Registros de ingreso</td><td class="text-right font-weight-bold">{{ $totalRegistros }}</td></tr>
                    <tr><td class="pl-4">Presentes</td><td class="text-right">{{ $presentes }} <small class="text-muted">({{ $porcentaje($presentes, $totalRegistros) }})</small></td></tr>
                    <tr><td class="pl-4">Tardanzas</td><td class="text-right">{{ $tardanzas }} <small class="text-muted">({{ $porcentaje($tardanzas, $totalRegistros) }})</small></td></tr>
                    <tr><td class="pl-4">Ausentes</td><td class="text-right">{{ $ausentes }} <small class="text-muted">({{ $porcentaje($ausentes, $totalRegistros) }})</small></td></tr>
                    <tr><td>Estudiantes activos</td><td class="text-right">{{ $activos }}</td></tr>
                    <tr><td>Promedio diario de estudiantes con ingreso registrado</td><td class="text-right">{{ $coberturaDiaria !== null ? $coberturaDiaria . ' %' : '—' }}</td></tr>
                </table>
            </div>
        </div>

        <div class="card card-outline card-success shadow-sm mb-4">
            <div class="card-header"><strong><i class="bi bi-diagram-3 mr-1"></i> ¿Cómo se registró cada ingreso?</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    @foreach($origen as $nombre => $cantidad)
                        @continue($nombre === 'Otros' && !$cantidad)
                        <tr><td>{{ $nombre }}</td><td class="text-right">{{ $cantidad }} <small class="text-muted">({{ $porcentaje($cantidad, $totalRegistros) }})</small></td></tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card card-outline card-success shadow-sm mb-4">
            <div class="card-header"><strong><i class="bi bi-cpu mr-1"></i> Lector QR (Raspberry Pi)</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr><td>Lecturas registradas</td><td class="text-right font-weight-bold">{{ $lecturasQr }}</td></tr>
                    <tr><td class="pl-4">Registradas al instante (con conexión)</td><td class="text-right">{{ $lecturasEnLinea }}</td></tr>
                    <tr><td class="pl-4">Guardadas sin conexión y enviadas después</td><td class="text-right">{{ $lecturasSincronizadas }}</td></tr>
                    <tr><td>Tiempo promedio desde la lectura hasta el registro</td><td class="text-right">{{ $segundosPromedio !== null ? $segundosPromedio . ' s' : '—' }}</td></tr>
                    <tr><td>Tiempo máximo (con conexión)</td><td class="text-right">{{ $segundosMaximo !== null ? $segundosMaximo . ' s' : '—' }}</td></tr>
                </table>
            </div>
        </div>

        <div class="card card-outline card-success shadow-sm mb-4">
            <div class="card-header"><strong><i class="bi bi-phone mr-1"></i> Avisos a padres y uso del sistema</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    @foreach($avisosPorTipo as $nombre => $cantidad)
                        <tr><td>Avisos: {{ $nombre }}</td><td class="text-right">{{ $cantidad }}</td></tr>
                    @endforeach
                    <tr><td>Enviados como notificación push</td><td class="text-right">{{ $avisosPush }} de {{ $avisos }}</td></tr>
                    <tr><td>Leídos en la app</td><td class="text-right">{{ $avisosLeidos }} de {{ $avisos }}</td></tr>
                    <tr><td>Tutores con la app instalada</td><td class="text-right">{{ $tutoresConApp }} de {{ $tutores }}</td></tr>
                    <tr><td>Comunicados enviados</td><td class="text-right">{{ $comunicados }}</td></tr>
                    <tr><td>Exámenes programados</td><td class="text-right">{{ $examenes }}</td></tr>
                    <tr><td>Asistencia de clase tomada por docentes</td><td class="text-right">{{ $asistenciaClase }} registros en {{ $clasesConAsistencia }} clase(s)</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
