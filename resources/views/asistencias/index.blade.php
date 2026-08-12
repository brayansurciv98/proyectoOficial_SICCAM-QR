@extends('layouts.app')

@section('title', 'Asistencias - SICCAM QR')
@section('page_title', 'Gestión de Asistencias')

@section('content')
<!-- Encabezado con botones de acción -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1 font-weight-bold text-dark">Registro de Asistencias</h5>
        <small class="text-muted">Fecha consultada: {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</small>
    </div>
    <div>
        <button class="btn btn-outline-success btn-sm mr-1">
            <i class="bi bi-file-earmark-excel mr-1"></i> Exportar Excel
        </button>
        <button class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-earmark-pdf mr-1"></i> Exportar PDF
        </button>
    </div>
</div>

<!-- Card de Filtros -->
<div class="card card-outline card-success shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('asistencias.index') }}" id="formFiltros">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small text-muted font-weight-bold">Fecha</label>
                    <input type="date" name="fecha" class="form-control" value="{{ request('fecha', $fecha) }}" onchange="document.getElementById('formFiltros').submit()">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted font-weight-bold">Curso</label>
                    <select name="curso" class="form-control custom-select" onchange="document.getElementById('formFiltros').submit()">
                        <option value="">Todos los cursos</option>
                        @foreach(['1ro Secundaria', '2do Secundaria', '3ro Secundaria', '4to Secundaria', '5to Secundaria', '6to Secundaria'] as $c)
                            <option value="{{ $c }}" {{ request('curso') == $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted font-weight-bold">Estado</label>
                    <select name="estado" class="form-control custom-select" onchange="document.getElementById('formFiltros').submit()">
                        <option value="">Todos</option>
                        <option value="presente" {{ request('estado') == 'presente' || request('estado') == 'a_tiempo' ? 'selected' : '' }}>Presente</option>
                        <option value="atraso" {{ request('estado') == 'atraso' ? 'selected' : '' }}>Tardanza</option>
                        <option value="ausente" {{ request('estado') == 'ausente' ? 'selected' : '' }}>Ausente</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted font-weight-bold">Buscar estudiante</label>
                    <div class="input-group">
                        <input type="text" name="buscar" class="form-control" placeholder="Nombre o código..." value="{{ request('buscar') }}">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de asistencias -->
<div class="card card-outline card-success shadow-sm">
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
                <tbody>
                    @forelse($asistencias as $asistencia)
                        <tr>
                            <td class="pl-4 font-weight-bold">{{ $asistencia->estudiante->codigo_estudiante ?? 'N/A' }}</td>
                            <td>{{ $asistencia->estudiante->nombres ?? '' }} {{ $asistencia->estudiante->apellidos ?? '' }}</td>
                            <td>
                                {{ $asistencia->estudiante->curso ?? '' }} 
                                {{ isset($asistencia->estudiante->paralelo) ? '- '.$asistencia->estudiante->paralelo : '' }}
                            </td>
                            <td>{{ \Carbon\Carbon::parse($asistencia->fecha)->format('d/m/Y') }}</td>
                            <td>{{ $asistencia->hora_entrada ? \Carbon\Carbon::parse($asistencia->hora_entrada)->format('H:i:s') : '—' }}</td>
                            <td>
                                @if(in_array($asistencia->estado, ['presente', 'a_tiempo']))
                                    <span class="badge badge-success px-2 py-1">Presente</span>
                                @elseif($asistencia->estado === 'atraso')
                                    <span class="badge badge-warning px-2 py-1">Tardanza</span>
                                @else
                                    <span class="badge badge-danger px-2 py-1">Ausente</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('asistencias.show', $asistencia) }}" class="btn btn-sm btn-outline-primary" title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
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
@endsection