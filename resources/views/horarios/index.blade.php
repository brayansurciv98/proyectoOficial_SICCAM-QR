@extends('layouts.app')

@section('title', 'Horarios - SICCAM QR')
@section('page_title', 'Horario Institucional')

@section('content')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold">Horarios por Bloque</h5>
    <a href="{{ route('horarios.create') }}" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> Nuevo horario
    </a>
</div>

<div class="card card-outline card-success shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label>Curso</label>
                <select name="curso" class="form-control">
                    <option value="">Todos</option>
                    @for($i = 1; $i <= 6; $i++)
                        <option value="{{ $i }}" {{ request('curso') == $i ? 'selected' : '' }}>{{ $i }}°</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-3">
                <label>Paralelo</label>
                <select name="paralelo" class="form-control">
                    <option value="">Todos</option>
                    @foreach(['A','B','C'] as $p)
                        <option value="{{ $p }}" {{ request('paralelo') == $p ? 'selected' : '' }}>{{ $p }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Día</label>
                <select name="dia" class="form-control">
                    <option value="">Todos</option>
                    @foreach([1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes'] as $num => $nom)
                        <option value="{{ $num }}" {{ request('dia') == $num ? 'selected' : '' }}>{{ $nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-success btn-block">Filtrar</button>
            </div>
        </form>
    </div>
</div>

@if($grilla !== null)
    {{-- Hoja de horario del curso (formato del instituto) --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="text-center mb-3">
                <h2 class="mb-0" style="letter-spacing: 2px;">
                    {{ request('curso') }}° {{ request('paralelo') }} SECUNDARIA
                </h2>
                <small class="text-muted">Unidad Educativa René Barrientos Ortuño "A" · Turno tarde</small>
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
                            <tr>
                                <th class="align-middle font-weight-normal" style="font-size: 1.5rem;">
                                    {{ \App\Models\Horario::nombreDia($dia) }}
                                </th>
                                @foreach(range(1, 4) as $bloque)
                                    @php $clasesCelda = $grilla[$bloque][$dia] ?? []; @endphp
                                    <td class="align-middle" style="height: 95px;">
                                        @forelse($clasesCelda as $h)
                                            <div class="font-weight-bold">
                                                {{ mb_strtoupper($h->materia->nombre ?? '', 'UTF-8') }}
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                {{ $h->docente ? $h->docente->nombres . ' ' . $h->docente->apellidos : '' }}
                                            </small>
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
@else
    <div class="alert alert-light border mb-3">
        <i class="bi bi-info-circle text-success mr-1"></i>
        Elija <strong>curso</strong> y <strong>paralelo</strong> y pulse <strong>Filtrar</strong> para ver la hoja de horario del curso.
    </div>
@endif


<div class="card card-outline card-success shadow-sm">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Día</th>
                    <th>Bloque</th>
                    <th>Hora</th>
                    <th>Curso</th>
                    <th>Materia</th>
                    <th>Docente</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($horarios as $h)
                    <tr>
                        <td>{{ \App\Models\Horario::nombreDia($h->dia) }}</td>
                        <td>{{ $h->bloque }}ª</td>
                        <td>{{ substr($h->hora_inicio, 0, 5) }} - {{ substr($h->hora_fin, 0, 5) }}</td>
                        <td>{{ $h->curso }}° {{ $h->paralelo }}</td>
                        <td>{{ $h->materia->nombre ?? '—' }}</td>
                        <td>{{ $h->docente ? $h->docente->nombres.' '.$h->docente->apellidos : '—' }}</td>
                        <td class="text-center">
                            <form action="{{ route('horarios.destroy', $h) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar este horario?')">
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
                        <td colspan="7" class="text-center text-muted py-4">
                            No hay horarios registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection