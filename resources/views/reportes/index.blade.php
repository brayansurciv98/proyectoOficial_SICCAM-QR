@extends('layouts.app')

@section('title', 'Reportes - SICCAM QR')
@section('page_title', 'Reportes de Asistencia')

@section('content')
@php
    // Los mismos filtros de la pantalla viajan al PDF y al Excel
    $filtros = [
        'fecha_desde'  => $fechaDesde,
        'fecha_hasta'  => $fechaHasta,
        'curso'        => $cursoFiltro,
        'tipo_reporte' => $tipoReporte,
    ];
@endphp

<!-- Encabezado con acciones -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1 font-weight-bold text-dark">Reportes de Asistencia</h5>
        <small class="text-muted">Generación y Consulta de Reportes Detallados</small>
    </div>
</div>

<!-- Card de Filtros del reporte -->
<div class="card card-outline card-success shadow-sm mb-4">
    <div class="card-header">
        <h3 class="card-title font-weight-bold text-dark"><i class="bi bi-funnel mr-1"></i> Filtros del reporte</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('reportes.index') }}">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small text-muted font-weight-bold">Fecha desde</label>
                    <input type="date" name="fecha_desde" class="form-control" value="{{ $fechaDesde }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted font-weight-bold">Fecha hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" value="{{ $fechaHasta }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted font-weight-bold">Curso</label>
                    <select name="curso" class="form-control custom-select">
                        <option value="">Todos los cursos</option>
                        @foreach($listaCursos as $valor => $nombre)
                            <option value="{{ $valor }}" {{ (string) $cursoFiltro === (string) $valor ? 'selected' : '' }}>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted font-weight-bold">Tipo de reporte</label>
                    <select name="tipo_reporte" class="form-control custom-select">
                        @foreach($tiposReporte as $valor => $nombre)
                            <option value="{{ $valor }}" {{ $tipoReporte === $valor ? 'selected' : '' }}>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-3 d-flex">
                <button type="submit" class="btn btn-success btn-sm mr-2">
                    <i class="bi bi-search mr-1"></i> Generar reporte
                </button>
                <a href="{{ route('reportes.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-clockwise mr-1"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tarjetas de métricas (Info-boxes de AdminLTE) -->
<div class="row mb-4">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm mb-3">
            <span class="info-box-icon bg-success elevation-1"><i class="bi bi-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Presentes</span>
                <span class="info-box-number h4 mb-0 font-weight-bold">{{ number_format($totalPresentes) }}</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm mb-3">
            <span class="info-box-icon bg-warning text-white elevation-1"><i class="bi bi-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Tardanzas</span>
                <span class="info-box-number h4 mb-0 font-weight-bold">{{ number_format($totalTardanzas) }}</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm mb-3">
            <span class="info-box-icon bg-danger elevation-1"><i class="bi bi-x-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Ausencias</span>
                <span class="info-box-number h4 mb-0 font-weight-bold">{{ number_format($totalAusencias) }}</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm mb-3">
            <span class="info-box-icon bg-primary elevation-1"><i class="bi bi-percent"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">% Asistencia</span>
                <span class="info-box-number h4 mb-0 font-weight-bold">{{ $porcentajeAsistencia }}%</span>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Resultados del Reporte -->
<div class="card card-outline card-success shadow-sm">
    <div class="card-header border-0 d-flex justify-content-between align-items-center py-3">
        <h3 class="card-title font-weight-bold text-dark m-0">
            Resultados: {{ $tiposReporte[$tipoReporte] }}
        </h3>
        <div class="card-tools m-0">
            <a href="{{ route('reportes.excel', $filtros) }}" class="btn btn-outline-success btn-sm mr-1">
                <i class="bi bi-file-earmark-excel mr-1"></i> Excel
            </a>
            <a href="{{ route('reportes.pdf', $filtros) }}" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf mr-1"></i> PDF
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">

            @if($tipoReporte === 'estudiante')
                <table class="table table-hover mb-0 align-middle">
                    <thead style="background-color: #e8f5e9;">
                        <tr>
                            <th class="pl-4">Código</th>
                            <th>Estudiante</th>
                            <th>Curso</th>
                            <th>Presentes</th>
                            <th>Tardanzas</th>
                            <th>Ausencias</th>
                            <th>% Asistencia</th>
                            <th>Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($porEstudiante as $e)
                            @php
                                $colorObs = ['Puntual' => 'success', 'Impuntual' => 'warning', 'Irregular' => 'danger'][$e['observacion']] ?? 'secondary';
                            @endphp
                            <tr>
                                <td class="pl-4">{{ $e['codigo'] }}</td>
                                <td class="font-weight-bold">{{ $e['nombre'] }}</td>
                                <td>{{ $e['curso'] }}</td>
                                <td>{{ $e['presentes'] }}</td>
                                <td>{{ $e['tardanzas'] }}</td>
                                <td>{{ $e['ausencias'] }}</td>
                                <td><strong>{{ $e['porcentaje'] }}%</strong></td>
                                <td><span class="badge badge-{{ $colorObs }}">{{ $e['observacion'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted">No hay estudiantes para este filtro.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-4 py-2 small text-muted">
                    Observación automática: <strong>Irregular</strong> = 20 % o más de ausencias ·
                    <strong>Impuntual</strong> = 20 % o más de tardanzas · <strong>Puntual</strong> = el resto.
                </div>

            @elseif(in_array($tipoReporte, ['tardanzas', 'ausencias']))
                <table class="table table-hover mb-0 align-middle">
                    <thead style="background-color: #e8f5e9;">
                        <tr>
                            <th class="pl-4">Fecha</th>
                            <th>Hora</th>
                            <th>Código</th>
                            <th>Estudiante</th>
                            <th>Curso</th>
                            <th>Registrado por</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($registros as $r)
                            <tr>
                                <td class="pl-4">{{ \Carbon\Carbon::parse($r->fecha)->format('d/m/Y') }}</td>
                                <td>{{ $r->hora_ingreso ? substr($r->hora_ingreso, 0, 5) : '—' }}</td>
                                <td>{{ $r->estudiante->codigo_estudiante ?? '' }}</td>
                                <td class="font-weight-bold">{{ $r->estudiante->apellidos ?? '' }} {{ $r->estudiante->nombres ?? '' }}</td>
                                <td>{{ $r->estudiante->curso ?? '' }}° {{ $r->estudiante->paralelo ?? '' }}</td>
                                <td>{{ $r->registrado_por }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">No hay registros en este periodo.</td></tr>
                        @endforelse
                    </tbody>
                </table>

            @else
                <table class="table table-hover mb-0 align-middle">
                    <thead style="background-color: #e8f5e9;">
                        <tr>
                            <th class="pl-4">Curso</th>
                            <th>Estudiantes Activos</th>
                            <th>Presentes</th>
                            <th>Tardanzas</th>
                            <th>Ausencias</th>
                            <th>% Asistencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reporteCursos as $fila)
                            <tr>
                                <td class="pl-4 font-weight-bold">{{ $fila['curso'] }}</td>
                                <td>{{ number_format($fila['total_estudiantes']) }}</td>
                                <td>{{ number_format($fila['presentes']) }}</td>
                                <td>{{ number_format($fila['tardanzas']) }}</td>
                                <td>{{ number_format($fila['ausencias']) }}</td>
                                <td>
                                    <strong class="{{ $fila['porcentaje'] >= 90 ? 'text-success' : 'text-danger' }}">
                                        {{ $fila['porcentaje'] }}%
                                    </strong>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    No se encontraron datos para generar el reporte.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif

        </div>
    </div>
</div>
@endsection