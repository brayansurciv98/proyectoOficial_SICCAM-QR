@extends('layouts.app')

@section('title', 'Listado de Estudiantes - SICCAM QR')
@section('page_title', 'Gestión de Estudiantes - Secundaria')

@section('content')

<!-- Alertas de éxito o error -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm mb-3" role="alert">
        <i class="bi bi-check-circle-fill mr-2"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<!-- Tarjeta Superior: Buscador, Filtros y Botón Nuevo -->
<div class="card card-success card-outline shadow-sm mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            
            <!-- Título del módulo -->
            <div class="col-xl-2 col-lg-3 col-md-12 mb-3 mb-lg-0">
                <h5 class="mb-0 font-weight-bold text-dark">
                    <i class="fas fa-user-graduate text-success mr-2"></i> Estudiantes
                </h5>
                <small class="text-muted">Total: <strong>{{ $estudiantes->count() }}</strong></small>
            </div>

            <!-- Filtros y Búsqueda -->
            <div class="col-xl-8 col-lg-7 col-md-12 mb-3 mb-lg-0">
                <div class="form-row">
                    <!-- Búsqueda General -->
                    <div class="form-group col-md-4 col-sm-6 mb-2 mb-md-0">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light border-right-0"><i class="bi bi-search text-muted"></i></span>
                            </div>
                            <input type="text" id="dtSearch" class="form-control border-left-0" placeholder="Buscar nombre, CI, código...">
                        </div>
                    </div>

                    <!-- Filtro por Curso (Únicamente Secundaria) -->
                    <div class="form-group col-md-3 col-sm-6 mb-2 mb-md-0">
                        <select id="dtCurso" class="form-control">
                            <option value="">Todos los Cursos</option>
                            <option value="1">1º</option>
                            <option value="2">2º</option>
                            <option value="3">3º</option>
                            <option value="4">4º</option>
                            <option value="5">5º</option>
                            <option value="6">6º</option>
                        </select>
                    </div>

                    <!-- Filtro por Paralelo -->
                    <div class="form-group col-md-2 col-sm-6 mb-2 mb-md-0">
                        <select id="dtParalelo" class="form-control">
                            <option value="">Paralelo</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                        </select>
                    </div>

                    <!-- Filtro por Estado -->
                    <div class="form-group col-md-3 col-sm-6 mb-0">
                        <select id="dtEstado" class="form-control">
                            <option value="">Todos los Estados</option>
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Botón de Registro -->
            <div class="col-xl-2 col-lg-2 col-md-12 text-md-right">
                <a href="{{ route('estudiantes.create') }}" class="btn btn-success font-weight-bold shadow-sm btn-block text-nowrap">
                    <i class="bi bi-person-plus-fill mr-1"></i> Nuevo
                </a>
            </div>

        </div>
    </div>
</div>

<!-- Tabla de Estudiantes -->
<div class="card card-success card-outline shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0" id="tablaEstudiantes">
                <thead class="bg-light">
                    <tr>
                        <th class="pl-3">Código</th>
                        <th>Estudiante</th>
                        <th>CI</th>
                        <th>Curso</th>
                        <th class="text-center">Paralelo</th>
                        <th>Tutor Principal</th>
                        <th>Código QR</th>
                        <th class="text-center">Estado</th>
                        <th class="text-right pr-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($estudiantes as $estudiante)
                        @php $tutor = $estudiante->tutores->first(); @endphp
                        <tr>
                            <!-- Código -->
                            <td class="pl-3 align-middle">
                                <span class="badge badge-light border text-monospace text-dark px-2 py-1">
                                    {{ $estudiante->codigo_estudiante }}
                                </span>
                            </td>

                            <!-- Nombre Estudiante -->
                            <td class="align-middle">
                                <strong class="text-dark d-block">{{ $estudiante->nombres }} {{ $estudiante->apellidos }}</strong>
                                @if($estudiante->genero)
                                    <small class="text-muted">
                                        Género: {{ $estudiante->genero == 'M' ? 'Masculino' : 'Femenino' }}
                                    </small>
                                @endif
                            </td>

                            <!-- CI -->
                            <td class="align-middle text-muted">
                                {{ $estudiante->ci ?? 'S/C' }}
                            </td>

                            <!-- Curso -->
                            <td class="align-middle font-weight-bold text-dark">
                                {{ $estudiante->curso }}
                            </td>

                            <!-- Paralelo -->
                            <td class="align-middle text-center">
                                <span class="badge badge-light border text-dark font-weight-bold px-2 py-1">
                                    {{ $estudiante->paralelo ?? '-' }}
                                </span>
                            </td>

                            <!-- Tutor -->
                            <td class="align-middle">
                                @if($tutor)
                                    <span class="d-block font-weight-bold text-dark">{{ $tutor->nombres }} {{ $tutor->apellidos }}</span>
                                    <small class="text-muted"><i class="bi bi-telephone mr-1"></i>{{ $tutor->telefono ?? 'Sin cel.' }}</small>
                                @else
                                    <span class="badge badge-warning text-dark"><i class="bi bi-exclamation-triangle mr-1"></i> Sin tutor</span>
                                @endif
                            </td>

                            <!-- Código QR -->
                            <td class="align-middle">
                                @if($estudiante->qr_imagen)
                                    <button type="button" class="btn btn-outline-success btn-sm" data-toggle="modal" data-target="#modalQr{{ $estudiante->id }}">
                                        <i class="bi bi-qr-code"></i> Ver QR
                                    </button>

                                    <!-- Modal Ver QR -->
                                    <div class="modal fade" id="modalQr{{ $estudiante->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header bg-success text-white py-2">
                                                    <h6 class="modal-title font-weight-bold">
                                                        <i class="bi bi-qr-code mr-1"></i> Credencial QR
                                                    </h6>
                                                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body text-center p-4">
                                                    <img src="{{ asset('storage/'.$estudiante->qr_imagen) }}" alt="QR" class="img-fluid border rounded p-2 mb-2" style="max-width: 200px;">
                                                    <h6 class="font-weight-bold mb-0 text-dark">{{ $estudiante->nombres }} {{ $estudiante->apellidos }}</h6>
                                                    <small class="text-muted d-block">{{ $estudiante->codigo_estudiante }}</small>
                                                </div>
                                                <div class="modal-footer py-2">
                                                    <a href="{{ asset('storage/'.$estudiante->qr_imagen) }}" download="QR_{{ $estudiante->codigo_estudiante }}.png" class="btn btn-success btn-sm btn-block">
                                                        <i class="bi bi-download mr-1"></i> Descargar
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="badge badge-secondary py-1 px-2">
                                        <i class="bi bi-x-circle mr-1"></i> Sin QR
                                    </span>
                                @endif
                            </td>

                            <!-- Estado -->
                            <td class="align-middle text-center">
                                <form action="{{ route('estudiantes.toggleEstado', $estudiante) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    @if($estudiante->estado === 'activo')
                                        <button type="submit" class="btn btn-sm btn-success px-2 py-1" title="Clic para desactivar">
                                            <i class="bi bi-check-circle mr-1"></i> Activo
                                        </button>
                                    @elseif($estudiante->estado === 'inactivo')
                                        <button type="submit" class="btn btn-sm btn-secondary px-2 py-1" title="Clic para activar">
                                            <i class="bi bi-dash-circle mr-1"></i> Inactivo
                                        </button>
                                    @else
                                        <span class="badge badge-danger px-2 py-1">{{ ucfirst($estudiante->estado) }}</span>
                                    @endif
                                </form>
                            </td>

                            <!-- Acciones -->
                            <td class="align-middle text-right pr-3">
                                <div class="d-flex justify-content-end align-items-center">
                                    <a href="{{ route('estudiantes.show', $estudiante) }}" class="btn btn-outline-info btn-sm mr-1" title="Ver Perfil">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('estudiantes.edit', $estudiante) }}" class="btn btn-outline-warning btn-sm mr-1" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    
                                    <button type="button" class="btn btn-outline-danger btn-sm" data-toggle="modal" data-target="#modalEliminar{{ $estudiante->id }}" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>

                                <!-- Modal Eliminación -->
                                <div class="modal fade text-left" id="modalEliminar{{ $estudiante->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white py-2">
                                                <h6 class="modal-title font-weight-bold">
                                                    <i class="bi bi-exclamation-triangle-fill mr-1"></i> Confirmar Eliminación
                                                </h6>
                                                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body text-center p-4">
                                                <i class="bi bi-trash text-danger display-4 d-block mb-2"></i>
                                                <h5>¿Deseas eliminar a este estudiante?</h5>
                                                <p class="text-muted mb-0">
                                                    Se eliminará permanentemente a <strong>{{ $estudiante->nombres }} {{ $estudiante->apellidos }}</strong> y su código QR.
                                                </p>
                                            </div>
                                            <div class="modal-footer justify-content-between py-2">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                                                <form action="{{ route('estudiantes.destroy', $estudiante) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm font-weight-bold">
                                                        <i class="bi bi-trash-fill mr-1"></i> Eliminar
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox display-4 d-block mb-2 text-secondary"></i>
                                No hay estudiantes registrados actualmente.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection