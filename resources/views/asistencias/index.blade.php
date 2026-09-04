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

<!-- SECCIÓN: Cámara Raspberry Pi y Panel de Control -->
<div class="row mb-4">
    <!-- Módulo Transmisión en Vivo (Cámara Raspberry Pi) -->
    <div class="col-lg-6 mb-3 mb-lg-0">
        <div class="card card-outline card-success shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                <h6 class="card-title font-weight-bold text-dark mb-0">
                    <i class="bi bi-camera-video-fill text-success mr-1"></i> Cámara de Control (Raspberry Pi)
                </h6>
                <span class="badge badge-success px-2 py-1"><i class="bi bi-broadcast mr-1"></i> EN VIVO</span>
            </div>
            <div class="card-body p-2 text-center bg-dark d-flex align-items-center justify-content-center" style="min-height: 260px;">
                <img src="http://10.48.41.8:8080/video"
                     class="img-fluid rounded border border-secondary" 
                     alt="Transmisión en vivo de la Raspberry Pi"
                     onerror="this.onerror=null; this.src='https://via.placeholder.com/640x480/1a1a1a/ffffff?text=Cámara+Raspberry+Pi+Desconectada';"
                     style="max-height: 300px; width: 100%; object-fit: contain;">
            </div>
            <div class="card-footer bg-white py-2 text-center">
                <small class="text-muted">
                    <i class="bi bi-qr-code-scan mr-1 text-primary"></i> Acerque la credencial con el código QR frente al lente para marcar asistencia.
                </small>
            </div>
        </div>
    </div>

    <!-- Módulo de Filtros y Búsqueda -->
    <div class="col-lg-6">
        <div class="card card-outline card-success shadow-sm h-100">
            <div class="card-header bg-white py-2">
                <h6 class="card-title font-weight-bold text-dark mb-0">
                    <i class="bi bi-funnel-fill text-success mr-1"></i> Filtros de Consulta
                </h6>
            </div>
            <div class="card-body d-flex flex-column justify-content-center">
                <form method="GET" action="{{ route('asistencias.index') }}" id="formFiltros">
                    <div class="row g-3">
                        <div class="col-md-6 mb-2">
                            <label class="form-label small text-muted font-weight-bold">Fecha</label>
                            <input type="date" name="fecha" class="form-control" value="{{ request('fecha', $fecha) }}" onchange="document.getElementById('formFiltros').submit()">
                        </div>

                        <div class="col-md-6 mb-2">
                            <label class="form-label small text-muted font-weight-bold">Curso</label>
                            <select name="curso" class="form-control custom-select" onchange="document.getElementById('formFiltros').submit()">
                                <option value="">Todos los cursos</option>
                                @foreach(['1' => '1º de Secundaria', '2' => '2º de Secundaria', '3' => '3º de Secundaria', '4' => '4º de Secundaria', '5' => '5º de Secundaria', '6' => '6º de Secundaria'] as $num => $label)
                                    <option value="{{ $num }}" {{ request('curso') == $num ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-2">
                            <label class="form-label small text-muted font-weight-bold">Estado</label>
                            <select name="estado" class="form-control custom-select" onchange="document.getElementById('formFiltros').submit()">
                                <option value="">Todos los estados</option>
                                <option value="presente" {{ request('estado') == 'presente' ? 'selected' : '' }}>Presente</option>
                                <option value="tardanza" {{ request('estado') == 'tardanza' ? 'selected' : '' }}>Tardanza</option>
                                <option value="ausente" {{ request('estado') == 'ausente' ? 'selected' : '' }}>Ausente</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-2">
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
            <div class="card-footer bg-white text-right py-2">
                <a href="{{ route('asistencias.index') }}" class="btn btn-link btn-sm text-muted p-0">
                    <i class="bi bi-arrow-counterclockwise"></i> Limpiar filtros
                </a>
            </div>
        </div>
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
                            <td>{{ $asistencia->estudiante->nombres ?? '' }} {{ $asistencia->estudiante->apellidos ?? '' }}</td>
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
@endsection

<!-- AQUÍ AL FINAL SE AGREGA EL SCRIPTS DE WEBSOCKETS -->
@push('scripts')
    <script src="{{ asset('js/asistencias.js') }}"></script>
@endpush