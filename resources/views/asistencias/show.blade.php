@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 font-weight-bold text-dark mb-0">Detalle de Asistencia</h2>
        <a href="{{ route('asistencias.index') }}" class="btn btn-secondary btn-sm">
            &larr; Volver a la lista
        </a>
    </div>

    <div class="row">
        <!-- Información de la Asistencia -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white font-weight-bold">
                    Registro de Asistencia #{{ $asistencia->id }}
                </div>
                <div class="card-body">
                    <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($asistencia->fecha)->format('d/m/Y') }}</p>

                    <!-- Muestra hora de ingreso o N/A si fue Falta -->
                    <p><strong>Hora de Ingreso:</strong> {{ $asistencia->hora_ingreso ?? 'No registró (Falta)' }}</p>

                    <!-- Badge dinámico según el Estado -->
                    <p>
                        <strong>Estado:</strong> 
                        @switch(strtolower($asistencia->estado))
                            @case('presente')
                                <span class="badge bg-success text-white">Presente</span>
                                @break
                            @case('tardanza')
                                <span class="badge bg-warning text-dark">Tardanza</span>
                                @break
                            @case('falta')
                                <span class="badge bg-danger text-white">Falta</span>
                                @break
                            @case('justificada')
                            @case('licencia')
                                <span class="badge bg-info text-white">Justificada / Licencia</span>
                                @break
                            @default
                                <span class="badge bg-secondary text-white">{{ ucfirst($asistencia->estado) }}</span>
                        @endswitch
                    </p>

                    <p><strong>Origen del registro:</strong> {{ $asistencia->registrado_por ?? 'Sistema' }}</p>

                    @if(!empty($asistencia->observacion))
                        <p><strong>Observación:</strong> {{ $asistencia->observacion }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Información del Estudiante -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white font-weight-bold">
                    Datos del Estudiante
                </div>
                <div class="card-body">
                    <p><strong>Código:</strong> {{ $asistencia->estudiante->codigo_estudiante }}</p>
                    <p><strong>Nombre:</strong> {{ $asistencia->estudiante->nombres }} {{ $asistencia->estudiante->apellidos }}</p>
                    <p><strong>Curso:</strong> {{ $asistencia->estudiante->curso }}º de Secundaria - {{ $asistencia->estudiante->paralelo }}</p>
                </div>
            </div>
        </div>

        <!-- Información de los Tutores (Útil en caso de faltas para llamar por teléfono) -->
        <div class="col-12 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info text-white font-weight-bold">
                    Contacto de Tutores
                </div>
                <div class="card-body">
                    @if($asistencia->estudiante->tutores && $asistencia->estudiante->tutores->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Parentesco</th>
                                        <th>Teléfono / Celular</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($asistencia->estudiante->tutores as $tutor)
                                        <tr>
                                            <td>{{ $tutor->nombres ?? $tutor->nombre }} {{ $tutor->apellidos ?? '' }}</td>
                                            <td>{{ $tutor->pivot->parentesco ?? $tutor->parentesco ?? 'Tutor' }}</td>
                                            <td>{{ $tutor->telefono ?? $tutor->celular ?? 'Sin registro' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No hay tutores registrados para este estudiante.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection