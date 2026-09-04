@extends('layouts.app')

@section('title', 'Asistencia Manual - SICCAM QR')
@section('page_title', 'Asistencia Manual')

@section('content')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card card-outline card-success shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('asistencias.manual') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label>Fecha</label>
                <input type="date" name="fecha" class="form-control" value="{{ $fecha }}">
            </div>
            <div class="col-md-2">
                <label>Curso</label>
                <select name="curso" class="form-control" required>
                    <option value="">—</option>
                    @for($i = 1; $i <= 6; $i++)
                        <option value="{{ $i }}" {{ (string)$curso === (string)$i ? 'selected' : '' }}>{{ $i }}°</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <label>Paralelo</label>
                <select name="paralelo" class="form-control">
                    <option value="">Todos</option>
                    <option value="A" {{ $paralelo == 'A' ? 'selected' : '' }}>A</option>
                    <option value="B" {{ $paralelo == 'B' ? 'selected' : '' }}>B</option>
                    <option value="C" {{ $paralelo == 'C' ? 'selected' : '' }}>C</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-success">Cargar estudiantes</button>
            </div>
        </form>
    </div>
</div>

@if($estudiantes->count())
<form method="POST" action="{{ route('asistencias.manual.store') }}">
    @csrf
    <input type="hidden" name="fecha" value="{{ $fecha }}">

    <div class="card card-outline card-success shadow-sm">
        <div class="card-header d-flex justify-content-between">
            <strong>Curso {{ $curso }}{{ $paralelo }} — {{ $fecha }}</strong>
            <button type="submit" class="btn btn-success btn-sm">
                <i class="fas fa-save"></i> Guardar asistencia
            </button>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Código</th>
                        <th>Estudiante</th>
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
                                    <label class="btn btn-outline-danger btn-sm {{ $actual == 'falta' ? 'active' : '' }}">
                                        <input type="radio" name="estados[{{ $est->id }}]" value="falta"
                                            {{ $actual == 'falta' ? 'checked' : '' }}> Falta
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
@elseif($curso)
    <div class="alert alert-warning">No hay estudiantes activos en ese curso/paralelo.</div>
@endif

@endsection