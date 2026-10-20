@extends('layouts.app')

@section('title', 'Docentes - SICCAM QR')
@section('page_title', 'Gestión de Docentes')

@section('content')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->has('docente'))
    <div class="alert alert-danger">{{ $errors->first('docente') }}</div>
@endif

@if(session('credenciales_docente'))
    @php $c = session('credenciales_docente'); @endphp
    <div class="alert alert-info">
        <strong>Docente registrado:</strong> {{ $c['nombre'] }}<br>
        <strong>Materia:</strong> {{ $c['materia'] }}<br>
        <strong>Usuario (CI/Email):</strong> {{ $c['usuario'] }}<br>
        <strong>Contraseña inicial:</strong> <code>{{ $c['password'] }}</code>
        <br><small>Guarda esta contraseña; el docente la usará para iniciar sesión.</small>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold">Listado de Docentes</h5>
    <a href="{{ route('docentes.create') }}" class="btn btn-verde">
        <i class="bi bi-person-plus-fill"></i> Nuevo Docente
    </a>
</div>

<div class="card card-outline card-success shadow-sm">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Nombre</th>
                    <th>CI</th>
                    <th>Materia</th>
                    <th>Cursos</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($docentes as $docente)
                    <tr>
                        <td class="font-weight-bold">{{ $docente->nombres }} {{ $docente->apellidos }}</td>
                        <td>{{ $docente->ci ?? '—' }}</td>
                        <td>{{ $docente->materia->nombre ?? '—' }}</td>
                        <td>
                            @foreach($docente->cursos as $curso)
                                <span class="badge badge-secondary">{{ $curso->curso }}{{ $curso->paralelo }}</span>
                            @endforeach
                        </td>
                        <td>
                            @if($docente->estado === 'activo')
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-danger">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('docentes.credenciales', $docente) }}"
                               class="btn btn-sm btn-outline-primary"
                               title="Ver credenciales">
                                <i class="bi bi-key"></i>
                            </a>

                            <a href="{{ route('docentes.edit', $docente) }}"
                               class="btn btn-sm btn-outline-success"
                               title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>

                            <form action="{{ route('docentes.destroy', $docente) }}"
                                  method="POST"
                                  class="d-inline"
                                  onsubmit="return confirm('¿Eliminar docente?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            No hay docentes registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal: ver credenciales sin cambiar contraseña --}}
@if(session('ver_credenciales_docente'))
    @php $c = session('ver_credenciales_docente'); @endphp
    <div class="modal fade show" tabindex="-1"
         style="display:block; background:rgba(0,0,0,.45);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-key-fill"></i> Credenciales del Docente
                    </h5>
                    <a href="{{ route('docentes.index') }}" class="close text-white">
                        <span>&times;</span>
                    </a>
                </div>
                <div class="modal-body">
                    <p class="mb-1"><strong>Nombre:</strong> {{ $c['nombre'] }}</p>
                    <p class="mb-1"><strong>Materia:</strong> {{ $c['materia'] }}</p>
                    <p class="mb-1"><strong>Usuario (CI/Email):</strong> {{ $c['usuario'] }}</p>
                    <p class="mb-0">
                        <strong>Contraseña inicial:</strong>
                        <code class="text-success">{{ $c['password'] }}</code>
                    </p>
                    <small class="text-muted">
                        Solo se muestran los datos guardados. No se cambió la contraseña.
                    </small>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('docentes.credenciales.pdf', $c['id']) }}"
                       class="btn btn-success">
                        <i class="bi bi-file-earmark-pdf"></i> Descargar PDF
                    </a>
                    <a href="{{ route('docentes.index') }}" class="btn btn-outline-secondary">
                        Cerrar
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif

@endsection