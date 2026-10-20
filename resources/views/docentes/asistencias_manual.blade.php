@extends('layouts.app')

@section('title', 'Asistencia Manual - Docente')
@section('page_title', 'Asistencia Manual')

@section('content')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card card-outline card-success shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('docente.asistencias.manual') }}" class="row align-items-end">
            <div class="col-md-4 form-group mb-md-0">
                <label>Curso / Paralelo</label>
                <select name="curso_paralelo" class="form-control" required>
                    <option value="">Seleccione...</option>
                    @foreach($cursosAsignados as $c)
                        @php
                            $value = $c['curso'] . '|' . $c['paralelo'];
                            $selected = ((string)($curso ?? '') === (string)$c['curso'] && ($paralelo ?? '') === $c['paralelo']);
                        @endphp
                        <option value="{{ $value }}" {{ $selected ? 'selected' : '' }}>
                            {{ $c['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 form-group mb-md-0">
                <label>Fecha</label>
                <input type="date" name="fecha" class="form-control" value="{{ $fecha }}" required>
            </div>

            <div class="col-md-3 form-group mb-md-0">
                <button type="submit" class="btn btn-success btn-block">
                    <i class="bi bi-search"></i> Cargar estudiantes
                </button>
            </div>
        </form>
    </div>
</div>

@if($estudiantes->count())
<form method="POST" action="{{ route('docente.asistencias.manual.store') }}">
    @csrf
    <input type="hidden" name="fecha" value="{{ $fecha }}">
    <input type="hidden" name="curso" value="{{ $curso }}">
    <input type="hidden" name="paralelo" value="{{ $paralelo }}">

    <div class="card card-outline card-success shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Estudiantes de {{ $curso }}° {{ $paralelo }} — {{ $fecha }}</strong>
            <button type="submit" class="btn btn-success btn-sm">
                <i class="fas fa-save"></i> Guardar asistencia
            </button>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>#</th>
                        <th>Código</th>
                        <th>Estudiante</th>
                        <th>Estado actual</th>
                        <th style="width:300px;">Marcar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($estudiantes as $i => $est)
                        @php
                            $asist = $asistenciasHoy->get($est->id);
                            $estadoActual = $asist->estado ?? '';
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $est->codigo_estudiante }}</td>
                            <td class="font-weight-bold">{{ $est->apellidos }} {{ $est->nombres }}</td>
                            <td>
                                @if($estadoActual === 'presente')
                                    <span class="badge badge-success">Presente</span>
                                @elseif($estadoActual === 'tardanza')
                                    <span class="badge badge-warning">Tardanza</span>
                                @elseif($estadoActual === 'ausente')
                                    <span class="badge badge-danger">Ausente</span>
                                @else
                                    <span class="text-muted">Sin registro</span>
                                @endif
                            </td>
                            <td>
                                <select name="asistencias[{{ $est->id }}][estado]" class="form-control form-control-sm" required>
                                    <option value="presente" {{ $estadoActual === 'presente' ? 'selected' : '' }}>Presente</option>
                                    <option value="tardanza" {{ $estadoActual === 'tardanza' ? 'selected' : '' }}>Tardanza</option>
                                    <option value="ausente" {{ $estadoActual === 'ausente' || $estadoActual === '' ? 'selected' : '' }}>Ausente</option>
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</form>
@elseif(!empty($curso) && !empty($paralelo))
    <div class="alert alert-warning">
        No hay estudiantes activos en {{ $curso }}° {{ $paralelo }}.
    </div>
@elseif($cursosAsignados->isEmpty())
    <div class="alert alert-info">
        No tienes cursos asignados. Contacta al administrador.
    </div>
@endif

@endsection