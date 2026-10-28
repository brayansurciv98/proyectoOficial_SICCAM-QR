@extends('layouts.app')

@section('title', 'Configuración - SICCAM QR')
@section('page_title', 'Configuración del Sistema')

@section('content')
@php
    // Tras un error de validación vuelve a la pestaña donde estaba el formulario
    $tabActiva = request('tab', old('tab_active', 'institucion'));
@endphp

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm mb-3" role="alert">
        <i class="bi bi-check-circle mr-1"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger shadow-sm mb-3">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
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
                                    <input type="text" name="colegio_nombre" class="form-control" maxlength="150" value="{{ old('colegio_nombre', $config['colegio_nombre'] ?? 'Unidad Educativa René Barrientos Ortuño "A"') }}" required>
                                    <small class="form-text text-muted">Aparece en el panel, los reportes y las credenciales en PDF.</small>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Código SIE</label>
                                    <input type="text" name="codigo_sie" class="form-control" inputmode="numeric" maxlength="10" pattern="[0-9]{6,10}" title="Entre 6 y 10 números" value="{{ old('codigo_sie', $config['codigo_sie'] ?? '') }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Dirección</label>
                                    <input type="text" name="colegio_direccion" class="form-control" maxlength="200" value="{{ old('colegio_direccion', $config['colegio_direccion'] ?? 'Cochabamba - Bolivia') }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Teléfono</label>
                                    <input type="text" name="colegio_telefono" class="form-control" maxlength="15" value="{{ old('colegio_telefono', $config['colegio_telefono'] ?? '') }}" placeholder="Ej. 4-1234567">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Correo institucional</label>
                                    <input type="email" name="colegio_email" class="form-control" maxlength="150" value="{{ old('colegio_email', $config['colegio_email'] ?? '') }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Director / Directora</label>
                                    <input type="text" name="colegio_director" class="form-control" maxlength="150" value="{{ old('colegio_director', $config['colegio_director'] ?? '') }}" placeholder="Nombre de la máxima autoridad">
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
                                    <label>Hora de Ingreso</label>
                                    <input type="time" name="hora_ingreso" class="form-control" value="{{ old('hora_ingreso', substr($config['hora_ingreso'] ?? '13:30', 0, 5)) }}" required>
                                    <small class="form-text text-muted">Más la tolerancia (pestaña Parámetros) es el límite para llegar puntual.</small>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Presente hasta</label>
                                    <input type="text" class="form-control" value="{{ $presenteHasta }}" readonly>
                                    <small class="form-text text-muted">Se calcula solo: hora de ingreso + tolerancia. Después, tardanza.</small>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Marcar ausencias a las</label>
                                    <input type="time" name="hora_marcar_ausencias" class="form-control" value="{{ old('hora_marcar_ausencias', $horaAusencias) }}" required>
                                    <small class="form-text text-muted">Quien no registró su ingreso queda como ausente y se avisa a su tutor.</small>
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
                                    <input type="number" name="tolerancia_minutos" class="form-control" min="0" max="60" step="1" value="{{ old('tolerancia_minutos', $config['tolerancia_minutos'] ?? '15') }}" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Avisar a los tutores las ausencias</label>
                                    @php $notificar = old('notificar_ausencia', $config['notificar_ausencia'] ?? 'si'); @endphp
                                    <select name="notificar_ausencia" class="form-control custom-select">
                                        <option value="si" {{ $notificar === 'si' ? 'selected' : '' }}>Sí</option>
                                        <option value="no" {{ $notificar === 'no' ? 'selected' : '' }}>No</option>
                                    </select>
                                    <small class="form-text text-muted">Si elige "No", las ausencias se registran igual, pero sin notificación.</small>
                                </div>
                                <div class="col-md-12 form-group mb-0">
                                    <label>Canal de notificación</label>
                                    <p class="form-control-plaintext pt-0">
                                        <i class="bi bi-phone text-success mr-1"></i>
                                        Notificación push en la app <strong>SICCAM Tutor</strong> (Firebase Cloud Messaging).
                                    </p>
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