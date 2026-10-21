@extends('layouts.app')

@section('title', 'Asistencia Manual - SICCAM QR')
@section('page_title', 'Asistencia Manual')

@section('content')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card card-outline card-success shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('asistencias.manual') }}" class="row align-items-end">
            <div class="col-md-2 form-group mb-md-0">
                <label>Fecha</label>
                <input type="date" name="fecha" class="form-control" value="{{ $fecha }}">
            </div>
            <div class="col-md-4 form-group mb-md-0">
                <label>Buscar estudiante</label>
                <input type="search" name="buscar" class="form-control" value="{{ $buscar }}"
                       placeholder="Nombre, apellido, código o CI">
            </div>
            <div class="col-md-2 form-group mb-md-0">
                <label>Curso</label>
                <select name="curso" class="form-control">
                    <option value="">Todos</option>
                    @for($i = 1; $i <= 6; $i++)
                        <option value="{{ $i }}" {{ (string)$curso === (string)$i ? 'selected' : '' }}>{{ $i }}°</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2 form-group mb-md-0">
                <label>Paralelo</label>
                <select name="paralelo" class="form-control">
                    <option value="">Todos</option>
                    <option value="A" {{ $paralelo == 'A' ? 'selected' : '' }}>A</option>
                    <option value="B" {{ $paralelo == 'B' ? 'selected' : '' }}>B</option>
                    <option value="C" {{ $paralelo == 'C' ? 'selected' : '' }}>C</option>
                </select>
            </div>
            <div class="col-md-2 form-group mb-md-0">
                <button class="btn btn-success btn-block">
                    <i class="bi bi-search"></i> Cargar
                </button>
            </div>
        </form>
        <small class="text-muted d-block mt-2">Escriba un nombre o apellido, elija un curso, o ambos.</small>
    </div>
</div>

@if($estudiantes->count())
<form method="POST" action="{{ route('asistencias.manual.store') }}">
    @csrf
    <input type="hidden" name="fecha" value="{{ $fecha }}">

    <div class="card card-outline card-success shadow-sm">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <strong class="mr-3">
                @if($curso)
                    Curso {{ $curso }}{{ $paralelo }}
                @else
                    Resultados de "{{ $buscar }}"
                @endif
                — {{ $fecha }} · {{ $estudiantes->count() }} estudiante(s)
            </strong>
            <div class="d-flex align-items-center mt-2 mt-md-0">
                <input type="search" class="form-control form-control-sm mr-2" style="width:230px"
                       data-filtro-tabla="tabla-manual" placeholder="Filtrar en esta lista...">
                <button type="submit" class="btn btn-success btn-sm text-nowrap">
                    <i class="fas fa-save"></i> Guardar asistencia
                </button>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0" id="tabla-manual">
                <thead class="bg-light">
                    <tr>
                        <th>Código</th>
                        <th>Estudiante</th>
                        <th>Curso</th>
                        <th width="40%">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($estudiantes as $est)
                        @php
                            $actual = $asistenciasHoy[$est->id]->estado ?? '';
                        @endphp
                        <tr>
                            <td>{{ $est->codigo_estudiante }}</td>
                            <td class="font-weight-bold">{{ $est->apellidos }} {{ $est->nombres }}</td>
                            <td>{{ $est->curso }}° {{ $est->paralelo }}</td>
                            <td>
                                <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                    <label class="btn btn-outline-success btn-sm {{ $actual == 'presente' ? 'active' : '' }}">
                                        <input type="radio" name="estados[{{ $est->id }}]" value="presente"
                                            {{ $actual == 'presente' ? 'checked' : '' }}> Presente
                                    </label>
                                    <label class="btn btn-outline-warning btn-sm {{ $actual == 'tardanza' ? 'active' : '' }}">
                                        <input type="radio" name="estados[{{ $est->id }}]" value="tardanza"
                                            {{ $actual == 'tardanza' ? 'checked' : '' }}> Tardanza
                                    </label>
                                    <label class="btn btn-outline-danger btn-sm {{ $actual == 'ausente' ? 'active' : '' }}">
                                        <input type="radio" name="estados[{{ $est->id }}]" value="ausente"
                                            {{ $actual == 'ausente' ? 'checked' : '' }}> Falta
                                    </label>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</form>
@elseif($buscar !== '')
    <div class="alert alert-warning">No se encontró ningún estudiante activo con "{{ $buscar }}"{{ $curso ? ' en ese curso' : '' }}.</div>
@elseif($curso)
    <div class="alert alert-warning">No hay estudiantes activos en ese curso/paralelo.</div>
@endif

@endsection

@push('scripts')
    <script src="{{ asset('js/filtro-tabla.js') }}"></script>
@endpush