@extends('layouts.app')

@section('title', 'Dashboard - SICCAM QR')
@section('page_title', 'Dashboard')

@section('content')
<!-- Mensaje de Bienvenida -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm text-white" style="background: linear-gradient(135deg, var(--verde-principal), var(--verde-claro)); border-radius: 12px;">
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
                <h3>1,248</h3>
                <p>Estudiantes</p>
            </div>
            <div class="icon">
                <i class="bi bi-people-fill"></i>
            </div>
            <a href="{{ route('estudiantes') }}" class="small-box-footer text-white">
                Ver lista completa <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Asistencias hoy -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success shadow-sm" style="border-radius: 10px;">
            <div class="inner">
                <h3>1,105</h3>
                <p>Asistencias hoy</p>
            </div>
            <div class="icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <a href="{{ route('asistencias') }}" class="small-box-footer">
                Más información <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Tardanzas -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning shadow-sm" style="border-radius: 10px;">
            <div class="inner text-white">
                <h3 class="text-white">87</h3>
                <p class="text-white">Tardanzas</p>
            </div>
            <div class="icon">
                <i class="bi bi-clock-fill"></i>
            </div>
            <a href="{{ route('asistencias') }}" class="small-box-footer text-white">
                Más información <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Ausencias -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger shadow-sm" style="border-radius: 10px;">
            <div class="inner">
                <h3>56</h3>
                <p>Ausencias</p>
            </div>
            <div class="icon">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <a href="{{ route('asistencias') }}" class="small-box-footer">
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
            <div class="card-body">
                <div class="alert alert-light border text-muted mb-0">
                    <i class="bi bi-info-circle mr-1"></i> Aquí se mostrarán las últimas asistencias en tiempo real mediante WebSocket/AJAX cuando conectemos la base de datos oficial.
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
                <a href="{{ route('estudiantes') }}" class="btn btn-outline-success btn-block text-left py-2 mb-2">
                    <i class="bi bi-person-plus-fill mr-2"></i> Registrar Estudiante
                </a>
                <a href="{{ route('reportes') }}" class="btn btn-outline-success btn-block text-left py-2 mb-2">
                    <i class="bi bi-file-earmark-bar-graph mr-2"></i> Ver Reportes
                </a>
                <a href="{{ route('comunicados') }}" class="btn btn-outline-success btn-block text-left py-2">
                    <i class="bi bi-send-fill mr-2"></i> Enviar Comunicado
                </a>
            </div>
        </div>
    </div>
</div>
@endsection