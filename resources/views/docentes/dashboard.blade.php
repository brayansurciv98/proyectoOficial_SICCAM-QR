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
        <p class="mb-0">
            <strong>Cursos asignados:</strong>
            @forelse($docente->cursos as $c)
                <span class="badge badge-success">{{ $c->curso }}{{ $c->paralelo }}</span>
            @empty
                <span class="text-muted">Sin cursos asignados</span>
            @endforelse
        </p>
    </div>
</div>
@endsection