@extends('layouts.app')

@section('title', 'Asistencia de clase - SICCAM QR')
@section('page_title', 'Asistencia de clase')

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
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 font-weight-bold">
                {{ $horario->curso }}° {{ $horario->paralelo }} — {{ $horario->materia->nombre ?? '' }}
            </h5>
            <small class="text-muted">
                Hoy {{ \Carbon\Carbon::parse($hoy)->format('d/m/Y') }} · Bloque {{ $horario->bloque }} ·
                {{ substr($horario->hora_inicio, 0, 5) }} a {{ substr($horario->hora_fin, 0, 5) }}
            </small>
        </div>

        <div class="btn-group mt-2 mt-md-0">
            <a href="{{ route('docente.clases.asistencia', ['horario' => $horario->id]) }}"
               class="btn btn-sm {{ $filtro === 'todos' ? 'btn-success' : 'btn-outline-success' }}">
                Lista general
            </a>
            <a href="{{ route('docente.clases.asistencia', ['horario' => $horario->id, 'filtro' => 'faltantes']) }}"
               class="btn btn-sm {{ $filtro === 'faltantes' ? 'btn-danger' : 'btn-outline-danger' }}">
                Solo faltantes
            </a>
            <a href="{{ route('docente.clases.index') }}" class="btn btn-sm btn-outline-secondary">Volver</a>
        </div>
    </div>
</div>

@if($estudiantes->isEmpty())
    <div class="alert alert-info">
        {{ $filtro === 'faltantes'
            ? 'Todos los estudiantes registraron su ingreso al colegio hoy.'
            : 'No hay estudiantes activos en este curso.' }}
    </div>
@else
<form method="POST" action="{{ route('docente.clases.asistencia.store', ['horario' => $horario->id]) }}">
    @csrf
    <input type="hidden" name="filtro" value="{{ $filtro }}">

    <div class="card card-outline card-success shadow-sm">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <strong class="mr-3">{{ $estudiantes->count() }} estudiante(s)</strong>
            <div class="d-flex align-items-center mt-2 mt-md-0">
                <input type="search" class="form-control form-control-sm mr-2" style="width:230px"
                       data-filtro-tabla="tabla-clase" placeholder="Buscar nombre, apellido o código...">
                <button type="submit" class="btn btn-success btn-sm text-nowrap">
                    <i class="fas fa-save"></i> Guardar asistencia
                </button>
            </div>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0" id="tabla-clase">
                <thead class="bg-light">
                    <tr>
                        <th>#</th>
                        <th>Estudiante</th>
                        <th>Ingreso al colegio</th>
                        <th style="width:220px;">En esta clase</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($estudiantes as $i => $est)
                        @php
                            $ingreso = $ingresos->get($est->id);
                            $marcado = $marcados->get($est->id);
                            // Si ya lo marcó, se respeta; si no: entró al colegio = presente, si no = ausente
                            $sugerido = $marcado->estado
                                ?? (($ingreso && $ingreso->estado !== 'ausente') ? 'presente' : 'ausente');
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="font-weight-bold">
                                <div class="d-flex align-items-center">
                                    <x-avatar rol="estudiante" :genero="$est->genero" tam="32" class="mr-2" />
                                    <div>
                                        {{ $est->apellidos }} {{ $est->nombres }}<br>
                                        <small class="text-muted">{{ $est->codigo_estudiante }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if(!$ingreso)
                                    <span class="badge badge-secondary">Sin ingreso</span>
                                @elseif($ingreso->estado === 'presente')
                                    <span class="badge badge-success">Presente {{ substr($ingreso->hora_ingreso ?? '', 0, 5) }}</span>
                                @elseif($ingreso->estado === 'tardanza')
                                    <span class="badge badge-warning">Tardanza {{ substr($ingreso->hora_ingreso ?? '', 0, 5) }}</span>
                                @else
                                    <span class="badge badge-danger">Ausente</span>
                                @endif
                            </td>
                            <td>
                                <select name="asistencias[{{ $est->id }}]" class="form-control form-control-sm">
                                    @foreach(['presente' => 'Presente', 'tardanza' => 'Tardanza', 'ausente' => 'Ausente'] as $valor => $texto)
                                        <option value="{{ $valor }}" {{ $sugerido === $valor ? 'selected' : '' }}>{{ $texto }}</option>
                                    @endforeach
                                </select>
                                @if($marcado)
                                    <small class="text-success"><i class="bi bi-check2"></i> guardado</small>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</form>
@endif

@endsection

@push('scripts')
    <script src="{{ asset('js/filtro-tabla.js') }}"></script>
@endpush