@extends('layouts.app')

@section('title', 'Exámenes - SICCAM QR')
@section('page_title', 'Programación de Exámenes')

@section('content')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold mr-3">Listado de exámenes</h5>
    <div class="d-flex align-items-center mt-2 mt-md-0">
        <input type="search" class="form-control mr-2" style="width:280px"
               data-filtro-tabla="tabla-examenes" placeholder="Buscar título, materia, curso o docente...">
        <a href="{{ route('examenes.create') }}" class="btn btn-verde text-nowrap">
            <i class="bi bi-plus-lg"></i> Programar examen
        </a>
    </div>
</div>

<div class="card card-outline card-success shadow-sm">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0" id="tabla-examenes">
            <thead class="bg-light">
                <tr>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Título</th>
                    <th>Materia</th>
                    <th>Curso</th>
                    <th>Docente</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($examenes as $examen)
                    <tr>
                        <td>{{ $examen->fecha?->format('d/m/Y') }}</td>
                        <td>{{ $examen->hora ? substr($examen->hora, 0, 5) : '—' }}</td>
                        <td class="font-weight-bold">{{ $examen->titulo }}</td>
                        <td>{{ $examen->materia->nombre ?? '—' }}</td>
                        <td>{{ $examen->curso }}{{ $examen->paralelo }}</td>
                        <td>{{ $examen->docente->nombres ?? '' }} {{ $examen->docente->apellidos ?? '' }}</td>
                        <td class="text-center">
                            <form action="{{ route('examenes.destroy', $examen) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar este examen?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr data-sin-filtro>
                        <td colspan="7" class="text-center py-4 text-muted">
                            No hay exámenes programados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/filtro-tabla.js') }}"></script>
@endpush