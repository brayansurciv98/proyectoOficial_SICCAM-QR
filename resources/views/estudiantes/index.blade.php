@extends('layouts.app')

@section('title', 'Listado de Estudiantes - SICCAM QR')
@section('page_title', 'Gestión de Estudiantes - Secundaria')

@section('content')

<!-- Alertas de éxito o error genéricas -->
@if(session('success') && !session('credenciales_tutor'))
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
                <small class="text-muted">Mostrando: <strong id="totalRegistros">{{ $estudiantes->count() }}</strong></small>
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
                            <input type="text" id="filterSearch" class="form-control border-left-0" placeholder="Buscar nombre, CI, código...">
                        </div>
                    </div>

                    <!-- Filtro por Curso -->
                    <div class="form-group col-md-3 col-sm-6 mb-2 mb-md-0">
                        <select id="filterCurso" class="form-control">
                            <option value="">Todos los Cursos</option>
                            <option value="1º">1º</option>
                            <option value="2º">2º</option>
                            <option value="3º">3º</option>
                            <option value="4º">4º</option>
                            <option value="5º">5º</option>
                            <option value="6º">6º</option>
                        </select>
                    </div>

                    <!-- Filtro por Paralelo -->
                    <div class="form-group col-md-2 col-sm-6 mb-2 mb-md-0">
                        <select id="filterParalelo" class="form-control">
                            <option value="">Paralelo</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                        </select>
                    </div>

                    <!-- Filtro por Estado -->
                    <div class="form-group col-md-3 col-sm-6 mb-0">
                        <select id="filterEstado" class="form-control">
                            <option value="">Todos los Estados</option>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
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
                        <tr id="rowEstudiante{{ $estudiante->id }}" 
                            data-search="{{ strtolower($estudiante->codigo_estudiante . ' ' . $estudiante->nombres . ' ' . $estudiante->apellidos . ' ' . $estudiante->ci) }}"
                            data-curso="{{ $estudiante->curso }}"
                            data-paralelo="{{ $estudiante->paralelo }}"
                            data-estado="{{ strtolower($estudiante->estado) }}">
                            
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
                                    
                                    <button type="button" class="btn btn-outline-danger btn-sm btn-trigger-delete" 
                                            data-id="{{ $estudiante->id }}" 
                                            data-nombre="{{ $estudiante->nombres }} {{ $estudiante->apellidos }}"
                                            data-url="{{ route('estudiantes.destroy', $estudiante) }}"
                                            title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="emptyRow">
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

<!-- Modal Único Reutilizable para Eliminación (Fuera de la tabla para evitar conflictos de z-index o nesting) -->
<div class="modal fade" id="modalEliminarGlobal" tabindex="-1" role="dialog" aria-hidden="true">
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
                    Se eliminará permanentemente a <strong id="deleteNombreEstudiante"></strong> y su código QR.
                </p>
            </div>
            <div class="modal-footer justify-content-between py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <form id="formEliminarGlobal" action="" method="POST">
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

<!-- Ventana Modal Sobrepuesta: Credenciales Generadas del Tutor -->
@if(session('credenciales_tutor'))
    @php 
        $cred = session('credenciales_tutor');
        $txtTexto = "==================================================\n"
                 . "       SICCAM TUTOR - CREDENCIALES DE ACCESO      \n"
                 . "==================================================\n\n"
                 . "Estudiante : " . $cred['estudiante'] . "\n"
                 . "Código EST : " . $cred['codigo_est'] . "\n"
                 . "Tutor      : " . $cred['tutor_nombre'] . "\n"
                 . "--------------------------------------------------\n"
                 . "DATOS DE INICIO DE SESIÓN EN APP MÓVIL:\n"
                 . "Usuario (CI/Email): " . $cred['usuario_ci'] . "\n"
                 . "Contraseña        : " . $cred['password'] . "\n"
                 . "--------------------------------------------------\n"
                 . "Guarde este archivo en un lugar seguro.\n";
        
        $txtUrl = "data:text/plain;charset=utf-8," . rawurlencode($txtTexto);
    @endphp

    <div class="modal fade show" id="modalCredencialesTutor" tabindex="-1" role="dialog" style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-weight-bold">
                        <i class="bi bi-key-fill mr-2"></i> Credenciales de Acceso para el Tutor
                    </h5>
                    <a href="{{ route('estudiantes.index') }}" class="close text-white" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </a>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 shadow-sm mb-3">
                        <i class="bi bi-info-circle-fill mr-1"></i>
                        Se ha registrado con éxito al estudiante y se han generado sus credenciales de acceso para la <strong>App Tutor</strong>.
                    </div>

                    <div class="card bg-light border p-3 mb-3">
                        <p class="mb-1"><strong>Estudiante:</strong> <span class="text-dark">{{ $cred['estudiante'] }} ({{ $cred['codigo_est'] }})</span></p>
                        <p class="mb-1"><strong>Tutor:</strong> <span class="text-dark">{{ $cred['tutor_nombre'] }}</span></p>
                        <hr class="my-2">
                        <p class="mb-1"><strong>Usuario (CI/Email):</strong> <span class="badge badge-dark text-monospace px-2 py-1 font-size-14" id="copyUser">{{ $cred['usuario_ci'] }}</span></p>
                        <p class="mb-0"><strong>Contraseña Generada:</strong> <span class="badge badge-success text-monospace px-2 py-1 font-size-14" id="copyPass">{{ $cred['password'] }}</span></p>
                    </div>

                    <p class="small text-muted mb-0">
                        * Descarga el archivo de texto o copia estos datos antes de cerrar este mensaje. La contraseña no volverá a mostrarse en texto plano por motivos de seguridad.
                    </p>
                </div>
                <div class="modal-footer justify-content-between bg-light">
                    <a href="{{ route('estudiantes.index') }}" class="btn btn-outline-secondary font-weight-bold">
                        <i class="bi bi-x-lg mr-1"></i> Cerrar
                    </a>
                    <div>
                        <button type="button" class="btn btn-outline-primary font-weight-bold mr-1" id="btnCopyCreds">
                            <i class="bi bi-clipboard mr-1"></i> Copiar
                        </button>
                        <a href="{{ $txtUrl }}" download="Credenciales_Tutor_{{ $cred['codigo_est'] }}.txt" class="btn btn-success font-weight-bold shadow-sm">
                            <i class="bi bi-file-earmark-arrow-down-fill mr-1"></i> Descargar (.TXT)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@endsection

@section('js')
    <script src="{{ asset('js/estudiantes-index.js') }}"></script>
@endsection