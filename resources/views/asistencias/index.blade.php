@extends('layouts.app')

@section('title', 'Asistencias - SICCAM QR')
@section('page_title', 'Gestión de Asistencias')

@section('content')
<!-- Encabezado con botones de acción -->
@php
    // Exportar la asistencia del día consultado (por estudiante), con el curso filtrado
    $filtrosExportar = [
        'fecha_desde'  => $fecha,
        'fecha_hasta'  => $fecha,
        'curso'        => request('curso'),
        'tipo_reporte' => 'estudiante',
    ];
    // La cámara se muestra aparte, en otro monitor (vista /pantalla de la Raspberry)
    $urlPantalla = config('services.raspberry.stream_url')
        ? str_replace('/video', '/pantalla', config('services.raspberry.stream_url'))
        : null;
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div class="mb-2 mb-md-0">
        <h5 class="mb-1 font-weight-bold text-dark">Registro de Asistencias</h5>
        <small class="text-muted">Fecha consultada: {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</small>
    </div>
    <div>
        @if($urlPantalla)
            <a href="{{ $urlPantalla }}" target="_blank" rel="noopener"
               class="btn btn-outline-secondary btn-sm mr-1"
               title="Abrir la cámara para mostrarla en otro monitor">
                <i class="bi bi-display mr-1"></i> Abrir cámara
            </a>
        @endif
        <a href="{{ route('reportes.excel', $filtrosExportar) }}" class="btn btn-outline-success btn-sm mr-1">
            <i class="bi bi-file-earmark-excel mr-1"></i> Exportar Excel
        </a>
        <a href="{{ route('reportes.pdf', $filtrosExportar) }}" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-earmark-pdf mr-1"></i> Exportar PDF
        </a>
    </div>
</div>

<!-- Filtros de consulta (ancho completo) -->
<div class="card card-outline card-success shadow-sm mb-4">
    <div class="card-header bg-white d-flex align-items-center py-2">
        <h6 class="card-title font-weight-bold text-dark mb-0">
            <i class="bi bi-funnel-fill text-success mr-1"></i> Filtros de Consulta
        </h6>
        <a href="{{ route('asistencias.index') }}" class="btn btn-link btn-sm text-muted p-0 ml-auto">
            <i class="bi bi-arrow-counterclockwise"></i> Limpiar filtros
        </a>
    </div>
    <div class="card-body pb-2">
        <form method="GET" action="{{ route('asistencias.index') }}" id="formFiltros">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <label class="form-label small text-muted font-weight-bold">Fecha</label>
                    <input type="date" name="fecha" class="form-control" value="{{ request('fecha', $fecha) }}" onchange="document.getElementById('formFiltros').submit()">
                </div>

                <div class="col-md-3 mb-2">
                    <label class="form-label small text-muted font-weight-bold">Curso</label>
                    <select name="curso" class="form-control custom-select" onchange="document.getElementById('formFiltros').submit()">
                        <option value="">Todos los cursos</option>
                        @foreach(['1' => '1º de Secundaria', '2' => '2º de Secundaria', '3' => '3º de Secundaria', '4' => '4º de Secundaria', '5' => '5º de Secundaria', '6' => '6º de Secundaria'] as $num => $label)
                            <option value="{{ $num }}" {{ request('curso') == $num ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 mb-2">
                    <label class="form-label small text-muted font-weight-bold">Estado</label>
                    <select name="estado" class="form-control custom-select" onchange="document.getElementById('formFiltros').submit()">
                        <option value="">Todos los estados</option>
                        <option value="presente" {{ request('estado') == 'presente' ? 'selected' : '' }}>Presente</option>
                        <option value="tardanza" {{ request('estado') == 'tardanza' ? 'selected' : '' }}>Tardanza</option>
                        <option value="ausente" {{ request('estado') == 'ausente' ? 'selected' : '' }}>Ausente</option>
                    </select>
                </div>

                <div class="col-md-3 mb-2">
                    <label class="form-label small text-muted font-weight-bold">Buscar estudiante</label>
                    <div class="input-group">
                        <input type="text" name="buscar" class="form-control" placeholder="Nombre o código..." value="{{ request('buscar') }}">
                        <div class="input-group-append">
                            <button class="btn btn-outline-success" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de asistencias -->
<div class="card card-outline card-success shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
        <h6 class="card-title font-weight-bold text-dark mb-0">
            <i class="bi bi-list-check text-success mr-1"></i> Asistencias Registradas
        </h6>
        <button onclick="window.location.reload();" class="btn btn-sm btn-outline-secondary" title="Actualizar lista">
            <i class="bi bi-arrow-clockwise"></i> Actualizar
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead style="background-color: #e8f5e9;">
                    <tr>
                        <th class="pl-4">Código</th>
                        <th>Estudiante</th>
                        <th>Curso</th>
                        <th>Fecha</th>
                        <th>Hora Ingreso</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <!-- AQUÍ VA EL ID EN EL TBODY -->
                <tbody id="tabla-asistencias-body">
                    @forelse($asistencias as $asistencia)
                        <tr>
                            <td class="pl-4 font-weight-bold text-primary">{{ $asistencia->estudiante->codigo_estudiante ?? 'N/A' }}</td>
                            <td><x-avatar rol="estudiante" :genero="$asistencia->estudiante->genero ?? null" tam="28" class="mr-2" />{{ $asistencia->estudiante->nombres ?? '' }} {{ $asistencia->estudiante->apellidos ?? '' }}</td>
                            <td>
                                {{ $asistencia->estudiante->curso ? $asistencia->estudiante->curso . 'º de Secundaria' : '' }} 
                                {{ isset($asistencia->estudiante->paralelo) ? '- ' . $asistencia->estudiante->paralelo : '' }}
                            </td>
                            <td>{{ \Carbon\Carbon::parse($asistencia->fecha)->format('d/m/Y') }}</td>
                            <td class="font-weight-bold">
                                {{ $asistencia->hora_ingreso ? \Carbon\Carbon::parse($asistencia->hora_ingreso)->format('H:i:s') : '—' }}
                            </td>
                            <td>
                                @if($asistencia->estado === 'presente')
                                    <span class="badge badge-success px-2 py-1"><i class="bi bi-check-circle mr-1"></i>Presente</span>
                                @elseif($asistencia->estado === 'tardanza')
                                    <span class="badge badge-warning px-2 py-1"><i class="bi bi-clock-history mr-1"></i>Tardanza</span>
                                @else
                                    <span class="badge badge-danger px-2 py-1"><i class="bi bi-x-circle mr-1"></i>Ausente</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('asistencias.show', $asistencia) }}" class="btn btn-sm btn-outline-primary" title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr id="fila-vacia">
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-info-circle mr-1"></i> No se encontraron registros de asistencia para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    @if($asistencias->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <small class="text-muted">Mostrando {{ $asistencias->firstItem() }} a {{ $asistencias->lastItem() }} de {{ $asistencias->total() }} registros</small>
            <div>
                {{ $asistencias->appends(request()->query())->links() }}
            </div>
        </div>
    @endif
</div>
{{-- Íconos para las filas que llegan en tiempo real (los usa public/js/asistencias.js) --}}
<template id="avatar-estudiante-M"><x-avatar rol="estudiante" genero="M" tam="28" class="mr-2" /></template>
<template id="avatar-estudiante-F"><x-avatar rol="estudiante" genero="F" tam="28" class="mr-2" /></template>
<template id="avatar-estudiante-"><x-avatar rol="estudiante" tam="28" class="mr-2" /></template>
@endsection

<!-- AQUÍ AL FINAL SE AGREGA EL SCRIPTS DE WEBSOCKETS -->
@push('scripts')
    <script src="{{ asset('js/asistencias.js') }}"></script>
@endpush