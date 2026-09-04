@extends('layouts.app')

@section('title', 'Docentes - SICCAM QR')
@section('page_title', 'Gestión de Docentes')

@section('content')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
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
                            @foreach($docente->cursos as $c)
                                <span class="badge badge-secondary">{{ $c->curso }}{{ $c->paralelo }}</span>
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
                            <a href="{{ route('docentes.edit', $docente) }}" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('docentes.destroy', $docente) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar docente?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No hay docentes registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection