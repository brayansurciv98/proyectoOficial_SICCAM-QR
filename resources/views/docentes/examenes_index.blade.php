@extends('layouts.app')

@section('title', 'Mis Exámenes - SICCAM QR')
@section('page_title', 'Mis Exámenes')

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

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold">Exámenes programados</h5>
    <a href="{{ route('docente.examenes.create') }}" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> Programar examen
    </a>
</div>

<div class="card card-outline card-success shadow-sm">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Título</th>
                    <th>Materia</th>
                    <th>Curso</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($examenes as $examen)
                    <tr>
                        <td class="font-weight-bold">{{ $examen->titulo }}</td>
                        <td>{{ $examen->materia->nombre ?? '—' }}</td>
                        <td>{{ $examen->curso }}° {{ $examen->paralelo }}</td>
                        <td>{{ \Carbon\Carbon::parse($examen->fecha)->format('d/m/Y') }}</td>
                        <td>{{ $examen->hora ? substr($examen->hora, 0, 5) : '—' }}</td>
                        <td class="text-center">
                            <button type="button"
                                    class="btn btn-sm btn-outline-primary btn-ver-examen"
                                    title="Ver detalle"
                                    data-titulo="{{ $examen->titulo }}"
                                    data-materia="{{ $examen->materia->nombre ?? '—' }}"
                                    data-curso="{{ $examen->curso }}° {{ $examen->paralelo }}"
                                    data-fecha="{{ \Carbon\Carbon::parse($examen->fecha)->format('d/m/Y') }}"
                                    data-hora="{{ $examen->hora ? substr($examen->hora, 0, 5) : '—' }}"
                                    data-observacion="{{ $examen->observacion ?: 'Sin observación' }}">
                                <i class="bi bi-eye"></i>
                            </button>

                            <form action="{{ route('docente.examenes.destroy', $examen) }}"
                                  method="POST"
                                  class="d-inline"
                                  onsubmit="return confirm('¿Eliminar este examen?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No tienes exámenes programados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal detalle --}}
<div class="modal fade" id="modalDetalleExamen" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Detalle del Examen</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-2"><strong>Título:</strong> <span id="det-titulo"></span></p>
                <p class="mb-2"><strong>Materia:</strong> <span id="det-materia"></span></p>
                <p class="mb-2"><strong>Curso:</strong> <span id="det-curso"></span></p>
                <p class="mb-2"><strong>Fecha:</strong> <span id="det-fecha"></span></p>
                <p class="mb-2"><strong>Hora:</strong> <span id="det-hora"></span></p>
                <p class="mb-0"><strong>Observación:</strong></p>
                <p id="det-observacion" class="text-muted mb-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    <script src="{{ asset('js/examenes-docente-index.js') }}"></script>
@endpush