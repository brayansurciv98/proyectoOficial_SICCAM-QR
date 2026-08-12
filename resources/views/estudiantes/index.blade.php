@extends('layouts.app')

@section('title', 'Estudiantes - SICCAM QR')
@section('page_title', 'Gestión de Estudiantes')

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

<!-- Encabezado de la sección -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1 font-weight-bold text-dark">Listado de Estudiantes</h5>
        <small class="text-muted">Total registrados: {{ $estudiantes->count() }}</small>
    </div>
    <a href="{{ route('estudiantes.create') }}" class="btn btn-verde">
        <i class="bi bi-person-plus-fill mr-1"></i> Nuevo Estudiante
    </a>
</div>

<!-- Card de búsqueda y filtros -->
<div class="card card-outline card-success shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    </div>
                    <input type="text" id="buscarEstudiante" class="form-control" placeholder="Buscar por nombre, código o CI...">
                </div>
            </div>
            <div class="col-md-3">
                <select id="filtroCurso" class="form-control custom-select">
                    <option value="">Todos los cursos</option>
                    <option value="1ro Secundaria">1ro Secundaria</option>
                    <option value="2do Secundaria">2do Secundaria</option>
                    <option value="3ro Secundaria">3ro Secundaria</option>
                    <option value="4to Secundaria">4to Secundaria</option>
                    <option value="5to Secundaria">5to Secundaria</option>
                    <option value="6to Secundaria">6to Secundaria</option>
                </select>
            </div>
            <div class="col-md-3">
                <select id="filtroEstado" class="form-control custom-select">
                    <option value="">Todos los estados</option>
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de estudiantes -->
<div class="card card-outline card-success shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" id="tablaEstudiantes">
                <thead style="background-color: #e8f5e9;">
                    <tr>
                        <th class="pl-4">Código</th>
                        <th>Nombre Completo</th>
                        <th>Curso</th>
                        <th>Tutor</th>
                        <th>QR</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($estudiantes as $estudiante)
                        <tr>
                            <td class="pl-4 font-weight-bold">{{ $estudiante->codigo_estudiante }}</td>
                            <td>{{ $estudiante->nombres }} {{ $estudiante->apellidos }}</td>
                            <td>{{ $estudiante->curso }} {{ $estudiante->paralelo ? '- '.$estudiante->paralelo : '' }}</td>
                            <td>
                                @if($estudiante->tutores->count() > 0)
                                    {{ $estudiante->tutores->first()->nombres }} {{ $estudiante->tutores->first()->apellidos }}
                                @else
                                    <span class="text-muted">Sin tutor</span>
                                @endif
                            </td>
                            <td>
                                @if($estudiante->qr_imagen)
                                    <a href="{{ asset('storage/'.$estudiante->qr_imagen) }}" target="_blank">
                                        <img src="{{ asset('storage/'.$estudiante->qr_imagen) }}" width="45" height="45" alt="QR" class="img-thumbnail">
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($estudiante->estado === 'activo')
                                    <span class="badge badge-success px-2 py-1">Activo</span>
                                @else
                                    <span class="badge badge-danger px-2 py-1">{{ ucfirst($estudiante->estado) }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('estudiantes.show', $estudiante) }}" class="btn btn-sm btn-outline-primary mr-1" title="Ver Carnet / QR">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('estudiantes.edit', $estudiante) }}" class="btn btn-sm btn-outline-success mr-1" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('estudiantes.toggle', $estudiante) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-warning mr-1" title="{{ $estudiante->estado === 'activo' ? 'Inhabilitar' : 'Activar' }}">
                                        <i class="bi bi-{{ $estudiante->estado === 'activo' ? 'pause' : 'play' }}-circle"></i>
                                    </button>
                                </form>
                                <form action="{{ route('estudiantes.destroy', $estudiante) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que deseas eliminar permanentemente a este estudiante?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                No hay estudiantes registrados todavía.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection