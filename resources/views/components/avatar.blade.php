{{--
    Avatar de una persona (no hay fotos): figura según el género, color según el rol.
    Uso: <x-avatar rol="estudiante" :genero="$estudiante->genero" tam="32" />
         <x-avatar rol="tutor" :parentesco="$tutor->pivot->parentesco" />   (Madre / Padre)
         <x-avatar rol="docente" />
--}}
@props(['rol' => 'estudiante', 'genero' => null, 'parentesco' => null, 'tam' => 32])
@php
    // El tutor no tiene género registrado: se toma del parentesco
    if ($rol === 'tutor' && !$genero) {
        $genero = match (mb_strtolower(trim((string) $parentesco))) {
            'madre' => 'F',
            'padre' => 'M',
            default => null,
        };
    }
    $genero = in_array($genero, ['M', 'F'], true) ? $genero : null;

    $c = [
        'estudiante' => ['fondo' => '#16a34a', 'detalle' => '#0b5a2b', 'texto' => 'Estudiante'],
        'tutor'      => ['fondo' => '#2563eb', 'detalle' => '#173f8a', 'texto' => 'Tutor'],
        'docente'    => ['fondo' => '#d97706', 'detalle' => '#7a4300', 'texto' => 'Docente'],
    ][$rol] ?? ['fondo' => '#64748b', 'detalle' => '#334155', 'texto' => 'Persona'];

    $piel = '#fdf7f2';
    $id = 'av' . \Illuminate\Support\Str::random(10);
@endphp
<svg width="{{ $tam }}" height="{{ $tam }}" viewBox="0 0 64 64" role="img" aria-label="{{ $c['texto'] }}"
     {{ $attributes->merge(['class' => 'avatar-persona flex-shrink-0']) }} style="vertical-align: middle;">
    <defs><clipPath id="{{ $id }}"><circle cx="32" cy="32" r="32"/></clipPath></defs>
    <g clip-path="url(#{{ $id }})">
        <rect width="64" height="64" fill="{{ $c['fondo'] }}"/>
        <g transform="translate(32 40) scale(1.14) translate(-32 -40)">
            @if($genero === 'F')
                <path d="M18.5 30 C17 15 24 11 32 11 C40 11 47 15 45.5 30 L47 44 C43 46 40 45 38.5 42 L25.5 42 C24 45 21 46 17 44 Z" fill="{{ $c['detalle'] }}"/>
            @endif
            <path d="M9 66 C10 49 20 43 32 43 C44 43 54 49 55 66 Z" fill="{{ $piel }}"/>
            @if($rol === 'estudiante')
                <path d="M27 44 L32 52 L37 44 Z" fill="{{ $c['detalle'] }}"/>
            @elseif($rol === 'tutor')
                <path d="M24 45 C27 50 37 50 40 45" stroke="{{ $c['detalle'] }}" stroke-width="2.4" fill="none" stroke-linecap="round"/>
            @else
                <path d="M26 44 L32 50 L38 44" stroke="{{ $c['detalle'] }}" stroke-width="2.4" fill="none" stroke-linejoin="round"/>
                <rect x="40" y="51" width="7" height="2.4" rx="1" fill="{{ $c['detalle'] }}"/>
            @endif
            <ellipse cx="32" cy="27" rx="10.5" ry="11.5" fill="{{ $piel }}"/>
            @if($genero === 'M')
                <path d="M20.8 27 C20 16.5 25 12.5 32 12.5 C39 12.5 44 16.5 43.2 27 C41.5 21.5 37.5 19.5 32 19.5 C26.5 19.5 22.5 21.5 20.8 27 Z" fill="{{ $c['detalle'] }}"/>
            @elseif($genero === 'F')
                <path d="M21.5 25 C22.5 17 27 14.5 32 14.5 C37 14.5 41.5 17 42.5 25 C39 21 35 19.8 31 20.3 C27.5 20.8 24 22.3 21.5 25 Z" fill="{{ $c['detalle'] }}"/>
            @else
                <path d="M21 26 C21 17 26 13.5 32 13.5 C38 13.5 43 17 43 26 C41 22 37 20.5 32 20.5 C27 20.5 23 22 21 26 Z" fill="{{ $c['detalle'] }}" opacity=".55"/>
            @endif
            @if($rol === 'docente')
                <g fill="none" stroke="{{ $c['detalle'] }}" stroke-width="1.6">
                    <circle cx="27.6" cy="27.5" r="3.4"/><circle cx="36.4" cy="27.5" r="3.4"/><path d="M31 27.2 H33"/>
                </g>
            @endif
        </g>
    </g>
</svg>
