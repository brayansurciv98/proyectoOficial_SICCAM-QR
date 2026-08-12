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

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-check-circle mr-1"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

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
                <form action="{{ route('comunicados.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="accion" id="accionForm" value="enviar">

                    <div class="form-group mb-3">
                        <label class="form-label small text-muted font-weight-bold">Título del comunicado</label>
                        <input type="text" name="titulo" class="form-control" placeholder="Ej: Reunión de padres de familia" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label small text-muted font-weight-bold">Destinatarios</label>
                        <select name="destinatario" class="form-control custom-select" required>
                            <option value="todos" selected>Todos los padres</option>
                            <option value="1">1ro Secundaria</option>
                            <option value="2">2do Secundaria</option>
                            <option value="3">3ro Secundaria</option>
                            <option value="4">4to Secundaria</option>
                            <option value="5">5to Secundaria</option>
                            <option value="6">6to Secundaria</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label small text-muted font-weight-bold">Mensaje</label>
                        <textarea name="mensaje" class="form-control" rows="5" placeholder="Escribe el contenido del comunicado..." required></textarea>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <button type="submit" onclick="document.getElementById('accionForm').value='enviar'" class="btn btn-success btn-block font-weight-bold mb-2">
                            <i class="bi bi-send mr-1"></i> Enviar Comunicado
                        </button>
                        <button type="submit" onclick="document.getElementById('accionForm').value='borrador'" class="btn btn-outline-secondary btn-block btn-sm">
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
                    <form method="GET" action="{{ route('comunicados.index') }}" id="formFiltro">
                        <select name="estado" class="form-control custom-select custom-select-sm" style="width: auto;" onchange="document.getElementById('formFiltro').submit()">
                            <option value="">Todos</option>
                            <option value="enviado" {{ request('estado') == 'enviado' ? 'selected' : '' }}>Enviados</option>
                            <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Borradores</option>
                        </select>
                    </form>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($comunicados as $comunicado)
                        <div class="list-group-item px-4 py-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="pr-3">
                                    <h6 class="mb-1 font-weight-bold text-dark">{{ $comunicado->titulo }}</h6>
                                    <p class="mb-1 text-muted small">{{ Str::limit($comunicado->mensaje, 90) }}</p>
                                    <small class="text-muted">
                                        <i class="bi bi-clock mr-1"></i> 
                                        @if($comunicado->destinatario === 'todos')
                                            Enviado a: Todos los padres
                                        @else
                                            Enviado a: {{ $comunicado->curso_destino }}ro Secundaria
                                        @endif
                                        · {{ $comunicado->created_at->format('d/m/Y H:i') }}
                                    </small>
                                </div>
                                <div class="d-flex align-items-center">
                                    @if($comunicado->estado === 'enviado')
                                        <span class="badge badge-success px-3 py-2 mr-2">Enviado</span>
                                    @else
                                        <span class="badge badge-secondary px-3 py-2 mr-2">Borrador</span>
                                    @endif

                                    <form action="{{ route('comunicados.destroy', $comunicado->id) }}" method="POST" onsubmit="return confirm('¿Desea eliminar este comunicado?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Eliminar">
                                            <i class="bi bi-trash fs-5"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-4 text-center text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-1"></i>
                            No hay comunicados registrados.
                        </div>
                    @endforelse
                </div>
            </div>
            @if($comunicados->hasPages())
                <div class="card-footer py-2">
                    {{ $comunicados->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection