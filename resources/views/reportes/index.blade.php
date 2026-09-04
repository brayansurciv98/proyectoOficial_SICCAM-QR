@extends('layouts.app')

@section('title', 'Reportes - SICCAM QR')
@section('page_title', 'Reportes de Asistencia')

@section('content')
<!-- Encabezado con acciones -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1 font-weight-bold text-dark">Reportes de Asistencia</h5>
        <small class="text-muted">Consulta de reportes detallados</small>
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
                        @foreach(['1ro Secundaria', '2do Secundaria', '3ro Secundaria', '4to Secundaria', '5to Secundaria', '6to Secundaria'] as $c)
                            <option value="{{ $c }}" {{ $cursoFiltro == $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted font-weight-bold">Tipo de reporte</label>
                    <select name="tipo_reporte" class="form-control custom-select">
                        <option value="general" {{ $tipoReporte == 'general' ? 'selected' : '' }}>Resumen general</option>
                        <option value="estudiante" {{ $tipoReporte == 'estudiante' ? 'selected' : '' }}>Por estudiante</option>
                        <option value="curso" {{ $tipoReporte == 'curso' ? 'selected' : '' }}>Por curso</option>
                        <option value="tardanzas" {{ $tipoReporte == 'tardanzas' ? 'selected' : '' }}>Tardanzas</option>
                        <option value="ausencias" {{ $tipoReporte == 'ausencias' ? 'selected' : '' }}>Ausencias</option>
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
        <h3 class="card-title font-weight-bold text-dark m-0">Resultados del reporte</h3>
        <div class="card-tools m-0">
            <button class="btn btn-outline-success btn-sm mr-1">
                <i class="bi bi-file-earmark-excel mr-1"></i> Excel
            </button>
            <button class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf mr-1"></i> PDF
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
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
                                No existen datos para generar el reporte.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection