@extends('layouts.app')

@section('title', 'Perfil del Estudiante - SICCAM QR')
@section('page_title', 'Perfil del Estudiante')

@section('content')

<!-- Encabezado con acciones (se oculta al imprimir) -->
<div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
    <div>
        <h5 class="mb-1 font-weight-bold text-dark">
            <i class="fas fa-id-card text-success mr-2"></i> Perfil y Carnet Digital
        </h5>
        <small class="text-muted">Código: <strong>{{ $estudiante->codigo_estudiante ?? 'S/C' }}</strong></small>
    </div>
    <div>
        <a href="{{ route('estudiantes.index') }}" class="btn btn-outline-secondary btn-sm mr-1">
            <i class="bi bi-arrow-left"></i> Volver al listado
        </a>
        <a href="{{ route('estudiantes.edit', $estudiante) }}" class="btn btn-success btn-sm">
            <i class="bi bi-pencil-square"></i> Editar Datos
        </a>
    </div>
</div>

<div class="row">

    {{-- ===================== COLUMNA IZQUIERDA: CARNET Y QR ===================== --}}
    <div class="col-md-4">
        <!-- Card Carnet / QR -->
        <div class="card card-success card-outline shadow-sm text-center">
            <div class="card-header">
                <h3 class="card-title font-weight-bold text-dark float-none">
                    <i class="bi bi-qr-code-scan mr-1 text-success"></i> Credencial / Código QR
                </h3>
            </div>
            <div class="card-body">
                <!-- Estado del Estudiante -->
                <div class="mb-3">
                    @if($estudiante->estado === 'activo')
                        <span class="badge badge-success px-3 py-2 font-weight-normal">
                            <i class="bi bi-check-circle-fill mr-1"></i> Estudiante Activo
                        </span>
                    @else
                        <span class="badge badge-danger px-3 py-2 font-weight-normal">
                            <i class="bi bi-x-circle-fill mr-1"></i> {{ ucfirst($estudiante->estado ?? 'Inactivo') }}
                        </span>
                    @endif
                </div>

                <!-- Imagen del Código QR -->
                <div class="p-3 bg-light rounded d-inline-block border mb-3">
                    @if($estudiante->qr_imagen)
                        <img src="{{ asset('storage/'.$estudiante->qr_imagen) }}" 
                             alt="Código QR de {{ $estudiante->nombres }}" 
                             class="img-fluid" 
                             style="max-width: 180px; height: auto;">
                    @else
                        <div class="text-muted p-4">
                            <i class="bi bi-qr-code display-4 d-block mb-2 text-secondary"></i>
                            <small>Sin imagen QR generada</small>
                        </div>
                    @endif
                </div>

                <h5 class="font-weight-bold mb-0 text-dark">{{ $estudiante->nombres }} {{ $estudiante->apellidos }}</h5>
                <p class="text-muted small mb-2">{{ $estudiante->curso }} {{ $estudiante->paralelo ? '- '.$estudiante->paralelo : '' }}</p>
                <p class="badge badge-light border text-monospace text-dark px-2 py-1">{{ $estudiante->codigo_estudiante }}</p>

                <!-- Botones de Acción (ocultos al imprimir) -->
                <div class="mt-3 d-print-none">
                    @if($estudiante->qr_imagen)
                        <a href="{{ asset('storage/'.$estudiante->qr_imagen) }}" download="QR_{{ $estudiante->codigo_estudiante }}.png" class="btn btn-outline-success btn-sm btn-block">
                            <i class="bi bi-download mr-1"></i> Descargar QR
                        </a>
                    @endif
                    <button onclick="window.print();" class="btn btn-outline-dark btn-sm btn-block mt-2">
                        <i class="bi bi-printer mr-1"></i> Imprimir Credencial
                    </button>
                </div>
            </div>
        </div>

        <!-- Métricas Rápidas del Estudiante (oculto al imprimir) -->
        <div class="card card-success card-outline shadow-sm d-print-none">
            <div class="card-header">
                <h3 class="card-title font-weight-bold text-dark">
                    <i class="bi bi-bar-chart-line mr-1 text-success"></i> Resumen de Asistencia
                </h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-check-circle text-success mr-2"></i> Presentes</span>
                        <span class="badge badge-success badge-pill font-weight-bold">{{ $estudiante->asistencias_count ?? $totalPresentes ?? 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-clock text-warning mr-2"></i> Tardanzas</span>
                        <span class="badge badge-warning text-white badge-pill font-weight-bold">{{ $estudiante->tardanzas_count ?? $totalTardanzas ?? 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-x-circle text-danger mr-2"></i> Ausencias</span>
                        <span class="badge badge-danger badge-pill font-weight-bold">{{ $estudiante->ausencias_count ?? $totalAusencias ?? 0 }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- ===================== COLUMNA DERECHA: DATOS PERSONALES Y TUTOR ===================== --}}
    <div class="col-md-8">

        <!-- Datos del Estudiante -->
        <div class="card card-success card-outline shadow-sm mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold text-dark">
                    <i class="fas fa-user-graduate mr-1 text-success"></i> Información Personal del Estudiante
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-6 mb-3">
                        <label class="text-muted small font-weight-bold d-block">NOMBRES</label>
                        <span class="h6 font-weight-bold text-dark">{{ $estudiante->nombres }}</span>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label class="text-muted small font-weight-bold d-block">APELLIDOS</label>
                        <span class="h6 font-weight-bold text-dark">{{ $estudiante->apellidos }}</span>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label class="text-muted small font-weight-bold d-block">CÉDULA DE IDENTIDAD (CI)</label>
                        <span class="text-dark">{{ $estudiante->ci ?? 'No registrado' }}</span>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label class="text-muted small font-weight-bold d-block">FECHA DE NACIMIENTO</label>
                        <span class="text-dark">
                            {{ $estudiante->fecha_nacimiento ? \Carbon\Carbon::parse($estudiante->fecha_nacimiento)->format('d/m/Y') : 'No registrada' }}
                        </span>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label class="text-muted small font-weight-bold d-block">GÉNERO</label>
                        <span class="text-dark">
                            @if($estudiante->genero == 'M') Masculino
                            @elseif($estudiante->genero == 'F') Femenino
                            @else {{ $estudiante->genero ?? 'No especificado' }}
                            @endif
                        </span>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label class="text-muted small font-weight-bold d-block">CURSO Y PARALELO</label>
                        <span class="badge badge-success px-2 py-1 font-weight-bold">
                            {{ $estudiante->curso }} {{ $estudiante->paralelo ? ' - Paralelo '.$estudiante->paralelo : '' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Datos del Tutor / Padre de Familia -->
        <div class="card card-success card-outline shadow-sm mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold text-dark">
                    <i class="fas fa-user-friends mr-1 text-success"></i> Datos del Tutor / Padre de Familia
                </h3>
            </div>
            <div class="card-body">
                @php $tutor = $estudiante->tutores->first(); @endphp

                @if($tutor)
                    <div class="row">
                        <div class="col-sm-6 mb-3">
                            <label class="text-muted small font-weight-bold d-block">NOMBRE COMPLETO</label>
                            <span class="h6 font-weight-bold text-dark">{{ $tutor->nombres }} {{ $tutor->apellidos }}</span>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label class="text-muted small font-weight-bold d-block">PARENTESCO</label>
                            <span class="badge badge-info px-2 py-1">{{ $tutor->pivot->parentesco ?? $tutor->parentesco ?? 'Tutor Legal' }}</span>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label class="text-muted small font-weight-bold d-block">CÉDULA DE IDENTIDAD (CI)</label>
                            <span class="text-dark">{{ $tutor->ci ?? 'No registrado' }}</span>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label class="text-muted small font-weight-bold d-block">TELÉFONO / CELULAR</label>
                            <span class="text-dark">
                                @if($tutor->telefono)
                                    <a href="https://wa.me/591{{ preg_replace('/[^0-9]/', '', $tutor->telefono) }}" target="_blank" class="text-success font-weight-bold d-print-none">
                                        <i class="bi bi-whatsapp mr-1"></i> {{ $tutor->telefono }}
                                    </a>
                                    <span class="d-none d-print-inline">{{ $tutor->telefono }}</span>
                                @else
                                    No registrado
                                @endif
                            </span>
                        </div>
                        <div class="col-sm-12 mb-3">
                            <label class="text-muted small font-weight-bold d-block">CORREO ELECTRÓNICO</label>
                            <span class="text-dark">
                                @if($tutor->email)
                                    <a href="mailto:{{ $tutor->email }}" class="text-primary d-print-none">
                                        <i class="bi bi-envelope mr-1"></i> {{ $tutor->email }}
                                    </a>
                                    <span class="d-none d-print-inline">{{ $tutor->email }}</span>
                                @else
                                    No registrado
                                @endif
                            </span>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle mr-2"></i> Este estudiante no tiene un tutor asignado.
                    </div>
                @endif
            </div>
        </div>

        <!-- Últimos registros de asistencias (se oculta al imprimir) -->
        @if(isset($estudiante->asistencias) && $estudiante->asistencias->count() > 0)
            <div class="card card-success card-outline shadow-sm d-print-none">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold text-dark">
                        <i class="bi bi-clock-history mr-1 text-success"></i> Últimas Asistencias Marcadas
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead style="background-color: #e8f5e9;">
                                <tr>
                                    <th class="pl-3">Fecha</th>
                                    <th>Hora de Entrada</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($estudiante->asistencias->take(5) as $asistencia)
                                    <tr>
                                        <td class="pl-3">{{ \Carbon\Carbon::parse($asistencia->fecha ?? $asistencia->created_at)->format('d/m/Y') }}</td>
                                        <td>{{ $asistencia->hora_entrada ?? \Carbon\Carbon::parse($asistencia->created_at)->format('H:i:s') }}</td>
                                        <td>
                                            @if(($asistencia->estado ?? '') === 'presente')
                                                <span class="badge badge-success">Presente</span>
                                            @elseif(($asistencia->estado ?? '') === 'tardanza')
                                                <span class="badge badge-warning text-white">Tardanza</span>
                                            @else
                                                <span class="badge badge-secondary">{{ ucfirst($asistencia->estado ?? 'Registrado') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>

@endsection