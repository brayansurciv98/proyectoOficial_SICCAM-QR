@extends('layouts.app')

@section('title', 'Configuración - SICCAM QR')
@section('page_title', 'Configuración del Sistema')

@section('content')
@php
    $tabActiva = request('tab', 'institucion');
@endphp

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm mb-3" role="alert">
        <i class="bi bi-check-circle mr-1"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="row">
    <!-- Menú interno con componentes Nav Pills de AdminLTE -->
    <div class="col-md-3">
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">Opciones</h3>
            </div>
            <div class="card-body p-2">
                <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <a class="nav-link {{ $tabActiva === 'institucion' ? 'active' : '' }}" id="tab-institucion" data-toggle="pill" href="#content-institucion" role="tab">
                        <i class="bi bi-building mr-2"></i> Datos Institución
                    </a>
                    <a class="nav-link {{ $tabActiva === 'horarios' ? 'active' : '' }}" id="tab-horarios" data-toggle="pill" href="#content-horarios" role="tab">
                        <i class="bi bi-clock mr-2"></i> Horarios de Ingreso
                    </a>
                    <a class="nav-link {{ $tabActiva === 'parametros' ? 'active' : '' }}" id="tab-parametros" data-toggle="pill" href="#content-parametros" role="tab">
                        <i class="bi bi-sliders mr-2"></i> Parámetros Asistencia
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido dinámico con Cards de AdminLTE -->
    <div class="col-md-9">
        <div class="tab-content" id="v-pills-tabContent">
            
            <!-- Tab: Datos de la Institución -->
            <div class="tab-pane fade {{ $tabActiva === 'institucion' ? 'show active' : '' }}" id="content-institucion" role="tabpanel">
                <div class="card card-outline card-success shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title font-weight-bold text-dark">Información de la Institución</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('configuracion.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="tab_active" value="institucion">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Nombre de la Unidad Educativa</label>
                                    <input type="text" name="colegio_nombre" class="form-control" value="{{ $config['colegio_nombre'] ?? 'Unidad Educativa René Barrientos Ortuño \'A\'' }}" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Código SIE</label>
                                    <input type="text" name="codigo_sie" class="form-control" value="{{ $config['codigo_sie'] ?? '12345678' }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Dirección</label>
                                    <input type="text" name="colegio_direccion" class="form-control" value="{{ $config['colegio_direccion'] ?? 'Cochabamba - Bolivia' }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Teléfono</label>
                                    <input type="text" name="colegio_telefono" class="form-control" value="{{ $config['colegio_telefono'] ?? '4-1234567' }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Correo institucional</label>
                                    <input type="email" name="colegio_email" class="form-control" value="{{ $config['colegio_email'] ?? 'info@renebarrientos.edu.bo' }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Director / Directora</label>
                                    <input type="text" name="colegio_director" class="form-control" value="{{ $config['colegio_director'] ?? '' }}" placeholder="Nombre de la máxima autoridad">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success font-weight-bold">
                                <i class="bi bi-save mr-1"></i> Guardar Cambios
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tab: Horarios de Ingreso -->
            <div class="tab-pane fade {{ $tabActiva === 'horarios' ? 'show active' : '' }}" id="content-horarios" role="tabpanel">
                <div class="card card-outline card-success shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title font-weight-bold text-dark">Horarios de Control</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('configuracion.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="tab_active" value="horarios">
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label>Hora de Ingreso (Puntual)</label>
                                    <input type="time" name="hora_ingreso" class="form-control" value="{{ $config['hora_ingreso'] ?? '07:45' }}" required>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Hora Límite de Atraso</label>
                                    <input type="time" name="hora_limite_atraso" class="form-control" value="{{ $config['hora_limite_atraso'] ?? '08:15' }}" required>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Cierre de Registro</label>
                                    <input type="time" name="hora_cierre_registro" class="form-control" value="{{ $config['hora_cierre_registro'] ?? '09:00' }}" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success font-weight-bold">
                                <i class="bi bi-save mr-1"></i> Guardar Horarios
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tab: Parámetros de Asistencia -->
            <div class="tab-pane fade {{ $tabActiva === 'parametros' ? 'show active' : '' }}" id="content-parametros" role="tabpanel">
                <div class="card card-outline card-success shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title font-weight-bold text-dark">Parámetros Generales</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('configuracion.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="tab_active" value="parametros">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Tolerancia (Minutos)</label>
                                    <input type="number" name="tolerancia_minutos" class="form-control" min="0" max="60" value="{{ $config['tolerancia_minutos'] ?? '15' }}" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Notificar Ausencia Automáticamente</label>
                                    <select name="notificar_ausencia" class="form-control custom-select">
                                        <option value="si" {{ ($config['notificar_ausencia'] ?? 'si') === 'si' ? 'selected' : '' }}>Sí</option>
                                        <option value="no" {{ ($config['notificar_ausencia'] ?? 'si') === 'no' ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Canal de Notificación</label>
                                    <select name="canal_notificacion" class="form-control custom-select">
                                        <option value="email_whatsapp" {{ ($config['canal_notificacion'] ?? 'email_whatsapp') === 'email_whatsapp' ? 'selected' : '' }}>Email y WhatsApp</option>
                                        <option value="email" {{ ($config['canal_notificacion'] ?? '') === 'email' ? 'selected' : '' }}>Solo Email</option>
                                        <option value="whatsapp" {{ ($config['canal_notificacion'] ?? '') === 'whatsapp' ? 'selected' : '' }}>Solo WhatsApp</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success font-weight-bold">
                                <i class="bi bi-save mr-1"></i> Guardar Parámetros
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection