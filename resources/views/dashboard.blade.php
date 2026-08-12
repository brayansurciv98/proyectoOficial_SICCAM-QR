@extends('layouts.app')

@section('title', 'Dashboard - SICCAM QR')
@section('page_title', 'Dashboard')

@section('content')
<!-- Mensaje de Bienvenida -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm text-white mb-4" style="background: linear-gradient(135deg, var(--verde-principal), var(--verde-claro)); border-radius: 12px;">
            <div class="card-body p-4">
                <h4 class="font-weight-bold mb-1">Bienvenido al Sistema de Control de Asistencia</h4>
                <p class="mb-0 opacity-75">Unidad Educativa René Barrientos Ortuño "A"</p>
            </div>
        </div>
    </div>
</div>

<!-- Tarjetas Estadísticas (Small Boxes de AdminLTE 3) -->
<div class="row">
    <!-- Estudiantes -->
    <div class="col-lg-3 col-6">
        <div class="small-box shadow-sm" style="background-color: var(--verde-claro); color: white; border-radius: 10px;">
            <div class="inner">
                <h3>{{ number_format($totalEstudiantes) }}</h3>
                <p>Estudiantes Activos</p>
            </div>
            <div class="icon">
                <i class="bi bi-people-fill"></i>
            </div>
            <a href="{{ route('estudiantes.index') }}" class="small-box-footer text-white">
                Ver lista completa <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Asistencias hoy -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success shadow-sm" style="border-radius: 10px;">
            <div class="inner">
                <h3>{{ number_format($asistenciasHoy) }}</h3>
                <p>Asistencias hoy</p>
            </div>
            <div class="icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <a href="{{ route('asistencias.index') }}" class="small-box-footer">
                Más información <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Tardanzas -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning shadow-sm" style="border-radius: 10px;">
            <div class="inner text-white">
                <h3 class="text-white">{{ number_format($tardanzasHoy) }}</h3>
                <p class="text-white">Tardanzas hoy</p>
            </div>
            <div class="icon">
                <i class="bi bi-clock-fill"></i>
            </div>
            <a href="{{ route('asistencias.index') }}" class="small-box-footer text-white">
                Más información <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Ausencias -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger shadow-sm" style="border-radius: 10px;">
            <div class="inner">
                <h3>{{ number_format($ausenciasHoy) }}</h3>
                <p>Ausencias hoy</p>
            </div>
            <div class="icon">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <a href="{{ route('asistencias.index') }}" class="small-box-footer">
                Más información <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- Sección de Tablas y Accesos Rápidos -->
<div class="row">
    <!-- Últimas asistencias registradas -->
    <div class="col-md-8">
        <div class="card card-outline card-success shadow-sm" style="border-radius: 10px;">
            <div class="card-header bg-white border-bottom-0 py-3">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="bi bi-clock-history text-success mr-2"></i>Últimas asistencias registradas
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="pl-3">Estudiante</th>
                                <th>Curso</th>
                                <th>Hora</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ultimasAsistencias as $asistencia)
                                <tr>
                                    <td class="pl-3 font-weight-bold">
                                        {{ $asistencia->estudiante->nombres }} {{ $asistencia->estudiante->apellidos }}
                                    </td>
                                    <td>
                                        {{ $asistencia->estudiante->curso }} {{ $asistencia->estudiante->paralelo ? '- '.$asistencia->estudiante->paralelo : '' }}
                                    </td>
                                    <td>
                                        {{ \Carbon\Carbon::parse($asistencia->hora_entrada ?? $asistencia->created_at)->format('H:i:s A') }}
                                    </td>
                                    <td>
                                        @if($asistencia->estado === 'atraso')
                                            <span class="badge badge-warning px-2 py-1">Tardanza</span>
                                        @else
                                            <span class="badge badge-success px-2 py-1">A tiempo</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="bi bi-info-circle mr-1"></i> No se han registrado asistencias el día de hoy.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="col-md-4">
        <div class="card card-outline card-success shadow-sm" style="border-radius: 10px;">
            <div class="card-header bg-white border-bottom-0 py-3">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="bi bi-lightning-charge text-warning mr-2"></i>Accesos rápidos
                </h3>
            </div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('estudiantes.create') }}" class="btn btn-outline-success btn-block text-left py-2 mb-2">
                    <i class="bi bi-person-plus-fill mr-2"></i> Registrar Estudiante
                </a>
                <a href="{{ route('reportes.index') }}" class="btn btn-outline-success btn-block text-left py-2 mb-2">
                    <i class="bi bi-file-earmark-bar-graph mr-2"></i> Ver Reportes
                </a>
                <a href="{{ route('comunicados.index') }}" class="btn btn-outline-success btn-block text-left py-2">
                    <i class="bi bi-send-fill mr-2"></i> Enviar Comunicado
                </a>
            </div>
        </div>
    </div>
</div>
@endsection