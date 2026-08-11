@extends('layouts.app')

@section('title', 'Estudiantes - SICCAM QR')
@section('page_title', 'Gestión de Estudiantes')

@section('content')
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
                    <input type="text" class="form-control" placeholder="Buscar por nombre o código...">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-control custom-select">
                    <option selected>Todos los cursos</option>
                    <option>1ro Secundaria</option>
                    <option>2do Secundaria</option>
                    <option>3ro Secundaria</option>
                    <option>4to Secundaria</option>
                    <option>5to Secundaria</option>
                    <option>6to Secundaria</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-control custom-select">
                    <option selected>Todos los estados</option>
                    <option>Activo</option>
                    <option>Inactivo</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de estudiantes -->
<div class="card card-outline card-success shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
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
                                    <img src="{{ asset('storage/'.$estudiante->qr_imagen) }}" width="55" alt="QR">
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
                                <a href="{{ route('estudiantes.show', $estudiante) }}" class="btn btn-sm btn-outline-primary mr-1" title="Ver">
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
                                <form action="{{ route('estudiantes.destroy', $estudiante) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que deseas eliminar este estudiante?')">
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