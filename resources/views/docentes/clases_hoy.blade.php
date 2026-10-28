@extends('layouts.app')

@section('title', 'Mi horario - SICCAM QR')
@section('page_title', 'Mi horario')

@section('content')
@php
    $etiquetas = [
        'en_curso'  => ['En curso', 'success'],
        'proxima'   => ['Próxima', 'primary'],
        'pendiente' => ['Más tarde', 'secondary'],
        'terminada' => ['Terminada', 'dark'],
    ];
    $hoyIso = now()->dayOfWeekIso;        // 1 = lunes ... 7 = domingo
    $horaActual = now()->format('H:i:s');
@endphp

<p class="text-muted">
    {{ ucfirst(now()->locale('es')->isoFormat('dddd D [de] MMMM')) }} ·
    {{ $docente->nombres }} {{ $docente->apellidos }}
</p>

{{-- ===== CLASES DE HOY ===== --}}
<h5 class="font-weight-bold mb-3">
    <i class="bi bi-calendar-day text-success mr-1"></i> Clases de hoy
</h5>

@forelse($clases as $clase)
    @php [$texto, $color] = $etiquetas[$clase->estado_clase]; @endphp

    <div class="card card-outline card-{{ $color }} shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
            <div class="mb-2 mb-md-0">
                <span class="badge badge-{{ $color }} mb-1">{{ $texto }}</span>
                @if($clase->asistencia_tomada)
                    <span class="badge badge-light border mb-1"><i class="bi bi-check2"></i> Asistencia tomada</span>
                @endif
                <h5 class="mb-0 font-weight-bold">
                    {{ $clase->curso }}° {{ $clase->paralelo }} — {{ $clase->materia->nombre ?? '' }}
                </h5>
                <small class="text-muted">
                    Bloque {{ $clase->bloque }} · {{ substr($clase->hora_inicio, 0, 5) }} a {{ substr($clase->hora_fin, 0, 5) }}
                </small>
            </div>

            <div>
                <a href="{{ route('docente.clases.asistencia', ['horario' => $clase->id]) }}" class="btn btn-success btn-sm">
                    <i class="bi bi-clipboard-check"></i>
                    {{ $clase->asistencia_tomada ? 'Ver / editar asistencia' : 'Tomar asistencia' }}
                </a>
                <a href="{{ route('docente.clases.asistencia', ['horario' => $clase->id, 'filtro' => 'faltantes']) }}"
                   class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-person-x"></i> Solo faltantes
                </a>
            </div>
        </div>
    </div>
@empty
    <div class="alert alert-info">Hoy no tiene clases según su horario.</div>
@endforelse

{{-- ===== HORARIO SEMANAL (formato hoja de horario) ===== --}}
<div class="card shadow-sm mt-4">
    <div class="card-body p-3">
        <div class="text-center mb-3">
            <h3 class="mb-0" style="letter-spacing: 1px;">
                HORARIO DE {{ mb_strtoupper($docente->nombres . ' ' . $docente->apellidos, 'UTF-8') }}
            </h3>
            <small class="text-muted">
                {{ \App\Models\Configuracion::colegio() }} · Turno tarde
            </small>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered text-center mb-0" style="min-width: 760px;">
                <thead>
                    <tr>
                        <th style="width: 130px;"></th>
                        @foreach(range(1, 4) as $bloque)
                            @php [$inicio, $fin] = \App\Models\Horario::horasBloque($bloque); @endphp
                            <th class="align-middle">
                                {{ $bloque }}° BLOQUE
                                <span class="font-weight-normal">({{ substr($inicio, 0, 5) }} – {{ substr($fin, 0, 5) }})</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach(range(1, 5) as $dia)
                        <tr class="{{ $dia === $hoyIso ? 'table-success' : '' }}">
                            <th class="align-middle font-weight-normal" style="font-size: 1.5rem;">
                                {{ \App\Models\Horario::nombreDia($dia) }}
                                @if($dia === $hoyIso)
                                    <br><span class="badge badge-success" style="font-size: .7rem;">HOY</span>
                                @endif
                            </th>

                            @foreach(range(1, 4) as $bloque)
                                @php
                                    [$inicio, $fin] = \App\Models\Horario::horasBloque($bloque);
                                    $enEsteBloque = $grilla[$bloque][$dia] ?? [];
                                    $esAhora = $dia === $hoyIso && $horaActual >= $inicio && $horaActual < $fin;
                                @endphp
                                <td class="align-middle {{ $esAhora ? 'bg-success text-white' : '' }}" style="height: 95px;">
                                    @forelse($enEsteBloque as $h)
                                        {{-- Arriba: curso (como el "aula" del ejemplo) --}}
                                        <small class="d-block {{ $esAhora ? '' : 'text-muted' }}">
                                            CURSO {{ $h->curso }}° {{ $h->paralelo }}
                                        </small>
                                        {{-- Centro: materia --}}
                                        <div class="font-weight-bold my-1">
                                            {{ mb_strtoupper($h->materia->nombre ?? '', 'UTF-8') }}
                                        </div>
                                        {{-- Abajo: marca de la clase actual --}}
                                        @if($esAhora)
                                            <span class="badge badge-light">AHORA</span>
                                        @endif
                                    @empty
                                        <span class="text-muted">—</span>
                                    @endforelse
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($sinHorario->isNotEmpty())
    <div class="alert alert-warning mt-3 mb-0">
        <i class="bi bi-exclamation-triangle mr-1"></i>
        Cursos asignados sin clases en Horarios:
        <strong>{{ $sinHorario->map(fn ($c) => $c->curso . '° ' . $c->paralelo)->implode(', ') }}</strong>.
        Pida al administrador que los registre para poder tomar asistencia y programar exámenes en esos cursos.
    </div>
@endif
@endsection