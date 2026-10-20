@extends('layouts.app')

@section('title', 'Panel Docente - SICCAM QR')
@section('page_title', 'Panel del Docente')

@section('content')
<div class="card card-outline card-success shadow-sm">
    <div class="card-body">
        <h4 class="font-weight-bold mb-3">
            Bienvenido, {{ $docente->nombres }} {{ $docente->apellidos }}
        </h4>

        <p class="mb-2">
            <strong>Materia:</strong> {{ $docente->materia->nombre ?? '—' }}
        </p>

        <p class="mb-3">
            <strong>Cursos asignados:</strong>
            @forelse($docente->cursos as $c)
                <span class="badge badge-success">{{ $c->curso }}{{ $c->paralelo }}</span>
            @empty
                <span class="text-muted">Sin cursos asignados</span>
            @endforelse
        </p>

        <a href="{{ route('docente.asistencias.manual') }}" class="btn btn-success">
            <i class="bi bi-clipboard-check"></i> Asistencia Manual
        </a>
    </div>
</div>
@php
    $enCurso = $jornada->firstWhere('estado_clase', 'en_curso');
    $proxima = $jornada->firstWhere('estado_clase', 'proxima');
@endphp

<div class="card card-outline card-primary shadow-sm mt-3">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">
            <i class="bi bi-clock-history text-primary mr-1"></i> Mi jornada de hoy
        </h3>
        <div class="card-tools">
            <a href="{{ route('docente.clases.index') }}" class="btn btn-sm btn-outline-primary">Ver mis clases</a>
        </div>
    </div>
    <div class="card-body">
        @if($jornada->isEmpty())
            <p class="text-muted mb-0">Hoy no tiene clases según su horario.</p>
        @else
            @if($enCurso)
                <p class="mb-2">
                    <span class="badge badge-success">En curso</span>
                    <strong>{{ $enCurso->curso }}° {{ $enCurso->paralelo }}</strong>
                    · {{ substr($enCurso->hora_inicio, 0, 5) }} a {{ substr($enCurso->hora_fin, 0, 5) }}
                    <a href="{{ route('docente.clases.asistencia', ['horario' => $enCurso->id]) }}" class="btn btn-success btn-sm ml-2">
                        <i class="bi bi-clipboard-check"></i> Tomar asistencia
                    </a>
                </p>
            @else
                <p class="mb-2"><span class="badge badge-secondary">Sin clase en este momento</span></p>
            @endif

            @if($proxima)
                <p class="mb-0">
                    <span class="badge badge-primary">Próxima</span>
                    <strong>{{ $proxima->curso }}° {{ $proxima->paralelo }}</strong>
                    · {{ substr($proxima->hora_inicio, 0, 5) }} a {{ substr($proxima->hora_fin, 0, 5) }}
                </p>
            @elseif(!$enCurso)
                <p class="mb-0 text-muted">Ya terminaron sus clases de hoy.</p>
            @endif
        @endif
    </div>
</div>

<div class="card card-outline card-danger shadow-sm mt-3">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">
            <i class="bi bi-person-x-fill text-danger mr-1"></i> Ausentes de hoy en mis cursos
        </h3>
    </div>
    <div class="card-body p-0">
        @if($ausentesHoy->isEmpty())
            <p class="text-muted p-3 mb-0">No hay ausentes registrados hoy. El cierre automático se hace a las 13:50.</p>
        @else
            <table class="table table-sm table-striped mb-0">
                <thead>
                    <tr>
                        <th class="pl-3">Curso</th>
                        <th>Estudiante</th>
                        <th>Código</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ausentesHoy as $a)
                        <tr>
                            <td class="pl-3">{{ $a->estudiante->curso }}° {{ $a->estudiante->paralelo }}</td>
                            <td>{{ $a->estudiante->apellidos }} {{ $a->estudiante->nombres }}</td>
                            <td>{{ $a->estudiante->codigo_estudiante }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection