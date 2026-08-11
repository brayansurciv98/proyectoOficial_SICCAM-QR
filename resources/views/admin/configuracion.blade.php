@extends('layouts.app')

@section('title', 'Configuración - SICCAM QR')
@section('page_title', 'Configuración del Sistema')

@section('content')
<div class="row">
    <!-- Menú interno con componentes Nav Pills de AdminLTE -->
    <div class="col-md-3">
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">Opciones</h3>
            </div>
            <div class="card-body p-2">
                <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <a class="nav-link active" id="tab-institucion" data-toggle="pill" href="#content-institucion" role="tab">
                        <i class="bi bi-building mr-2"></i> Datos Institución
                    </a>
                    <a class="nav-link" id="tab-horarios" data-toggle="pill" href="#content-horarios" role="tab">
                        <i class="bi bi-clock mr-2"></i> Horarios de Ingreso
                    </a>
                    <a class="nav-link" id="tab-parametros" data-toggle="pill" href="#content-parametros" role="tab">
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
            <div class="tab-pane fade show active" id="content-institucion" role="tabpanel">
                <div class="card card-outline card-success shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title font-weight-bold text-dark">Información de la Institución</h3>
                    </div>
                    <div class="card-body">
                        <form onsubmit="return false;">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Nombre de la Unidad Educativa</label>
                                    <input type="text" class="form-control" value="Unidad Educativa René Barrientos Ortuño 'A'">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Código SIE</label>
                                    <input type="text" class="form-control" value="12345678">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Dirección</label>
                                    <input type="text" class="form-control" value="Cochabamba - Bolivia">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Teléfono</label>
                                    <input type="text" class="form-control" value="4-1234567">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Correo institucional</label>
                                    <input type="email" class="form-control" value="info@renebarrientos.edu.bo">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Director / Directora</label>
                                    <input type="text" class="form-control" placeholder="Nombre de la máxima autoridad">
                                </div>
                            </div>
                            <button class="btn btn-verde">
                                <i class="bi bi-save mr-1"></i> Guardar Cambios
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tab: Horarios de Ingreso -->
            <div class="tab-pane fade" id="content-horarios" role="tabpanel">
                <div class="card card-outline card-success shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title font-weight-bold text-dark">Horarios de Control</h3>
                    </div>
                    <div class="card-body">
                        <form onsubmit="return false;">
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label>Hora de Ingreso (Puntual)</label>
                                    <input type="time" class="form-control" value="07:45">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Hora Límite de Atraso</label>
                                    <input type="time" class="form-control" value="08:15">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Cierre de Registro</label>
                                    <input type="time" class="form-control" value="09:00">
                                </div>
                            </div>
                            <button class="btn btn-verde">
                                <i class="bi bi-save mr-1"></i> Guardar Horarios
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tab: Parámetros de Asistencia -->
            <div class="tab-pane fade" id="content-parametros" role="tabpanel">
                <div class="card card-outline card-success shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title font-weight-bold text-dark">Parámetros Generales</h3>
                    </div>
                    <div class="card-body">
                        <form onsubmit="return false;">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Tolerancia (Minutos)</label>
                                    <input type="number" class="form-control" value="15">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Notificar Ausencia Automáticamente</label>
                                    <select class="form-control custom-select">
                                        <option selected>Sí</option>
                                        <option>No</option>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Canal de Notificación</label>
                                    <select class="form-control custom-select">
                                        <option selected>Email y WhatsApp</option>
                                        <option>Solo Email</option>
                                        <option>Solo WhatsApp</option>
                                    </select>
                                </div>
                            </div>
                            <button class="btn btn-verde">
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