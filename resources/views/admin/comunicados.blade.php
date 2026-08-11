@extends('layouts.app')

@section('title', 'Comunicados - SICCAM QR')
@section('page_title', 'Comunicados a Padres de Familia')

@section('content')
<!-- Encabezado con título e instrucciones -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1 font-weight-bold text-dark">Comunicados a Padres de Familia</h5>
        <small class="text-muted">Envío y gestión de notificaciones</small>
    </div>
</div>

<div class="row">
    <!-- Formulario de nuevo comunicado -->
    <div class="col-lg-5 mb-4">
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header border-bottom-0 py-3">
                <h3 class="card-title font-weight-bold text-dark">
                    <i class="bi bi-plus-circle mr-1 text-success"></i> Nuevo Comunicado
                </h3>
            </div>
            <div class="card-body pt-0">
                <form>
                    <div class="form-group mb-3">
                        <label class="form-label small text-muted font-weight-bold">Título del comunicado</label>
                        <input type="text" class="form-control" placeholder="Ej: Reunión de padres de familia">
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label small text-muted font-weight-bold">Destinatarios</label>
                        <select class="form-control custom-select">
                            <option selected>Todos los padres</option>
                            <option>1ro Secundaria</option>
                            <option>2do Secundaria</option>
                            <option>3ro Secundaria</option>
                            <option>4to Secundaria</option>
                            <option>5to Secundaria</option>
                            <option>6to Secundaria</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label small text-muted font-weight-bold">Mensaje</label>
                        <textarea class="form-control" rows="5" placeholder="Escribe el contenido del comunicado..."></textarea>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <button type="button" class="btn btn-success btn-block font-weight-bold mb-2">
                            <i class="bi bi-send mr-1"></i> Enviar Comunicado
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-block btn-sm">
                            Guardar como borrador
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Lista de comunicados recientes -->
    <div class="col-lg-7 mb-4">
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-dark m-0">Comunicados recientes</h3>
                <div class="card-tools m-0">
                    <select class="form-control custom-select custom-select-sm" style="width: auto;">
                        <option>Todos</option>
                        <option>Enviados</option>
                        <option>Borradores</option>
                    </select>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    
                    <div class="list-group-item px-4 py-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1 font-weight-bold text-dark">Reunión general de padres</h6>
                                <p class="mb-1 text-muted small">Se convoca a todos los padres de familia a la reunión general...</p>
                                <small class="text-muted"><i class="bi bi-clock mr-1"></i> Enviado a: Todos los padres · 25/07/2026 10:15</small>
                            </div>
                            <span class="badge badge-success px-3 py-2">Enviado</span>
                        </div>
                    </div>

                    <div class="list-group-item px-4 py-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1 font-weight-bold text-dark">Aviso de suspensión de clases</h6>
                                <p class="mb-1 text-muted small">Por motivos de mantenimiento, las clases del día viernes...</p>
                                <small class="text-muted"><i class="bi bi-clock mr-1"></i> Enviado a: 4to y 5to Secundaria · 22/07/2026 14:30</small>
                            </div>
                            <span class="badge badge-success px-3 py-2">Enviado</span>
                        </div>
                    </div>

                    <div class="list-group-item px-4 py-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1 font-weight-bold text-dark">Recordatorio de pago de pensiones</h6>
                                <p class="mb-1 text-muted small">Se recuerda a los padres de familia ponerse al día...</p>
                                <small class="text-muted"><i class="bi bi-clock mr-1"></i> Borrador · 20/07/2026</small>
                            </div>
                            <span class="badge badge-secondary px-3 py-2">Borrador</span>
                        </div>
                    </div>

                    <div class="list-group-item px-4 py-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1 font-weight-bold text-dark">Entrega de libretas de notas</h6>
                                <p class="mb-1 text-muted small">La entrega de libretas se realizará el día lunes...</p>
                                <small class="text-muted"><i class="bi bi-clock mr-1"></i> Enviado a: Todos los padres · 15/07/2026 09:00</small>
                            </div>
                            <span class="badge badge-success px-3 py-2">Enviado</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection