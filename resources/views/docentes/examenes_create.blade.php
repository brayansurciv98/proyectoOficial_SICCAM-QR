@extends('layouts.app')

@section('title', 'Programar Examen - SICCAM QR')
@section('page_title', 'Programar Examen')

@section('content')

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if($horarios->isNotEmpty())
    <div class="card card-outline card-info shadow-sm">
        <div class="card-header py-2">
            <h3 class="card-title font-weight-bold">
                <i class="bi bi-calendar-week text-info mr-1"></i>
                Mis clases (solo puede programar exámenes en estos días y horas)
            </h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0">
                <thead>
                    <tr>
                        <th class="pl-3">Día</th>
                        <th>Horario</th>
                        <th>Curso</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($horarios as $h)
                        <tr>
                            <td class="pl-3">{{ \App\Models\Horario::nombreDia($h->dia) }}</td>
                            <td>{{ substr($h->hora_inicio, 0, 5) }} – {{ substr($h->hora_fin, 0, 5) }}</td>
                            <td>{{ $h->curso }}° {{ $h->paralelo }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="alert alert-warning">
        Todavía no tiene horarios asignados. Pida al administrador que registre sus clases para poder programar exámenes.
    </div>
@endif


<form method="POST" action="{{ route('docente.examenes.store') }}"
      id="form-examen"
      data-docente="{{ $docente->id }}"
      data-horarios='@json($horariosJs)'>
    @csrf

    <div class="card card-outline card-success shadow-sm">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Docente</label>
                    <input type="text" class="form-control"
                           value="{{ $docente->nombres }} {{ $docente->apellidos }}" readonly>
                </div>

                <div class="col-md-6 form-group">
                    <label>Materia</label>
                    <input type="text" class="form-control"
                           value="{{ $docente->materia->nombre ?? '—' }}" readonly>
                </div>

                <div class="col-md-6 form-group">
                    <label>Curso / Paralelo <span class="text-danger">*</span></label>
                    <select name="curso_paralelo" id="curso_paralelo" class="form-control" required>
                        <option value="">Seleccione...</option>
                        @foreach($docente->cursos as $c)
                            @php $value = $c->curso . '|' . $c->paralelo; @endphp
                            <option value="{{ $value }}"
                                {{ old('curso') == $c->curso && old('paralelo') == $c->paralelo ? 'selected' : '' }}>
                                {{ $c->curso }}° {{ $c->paralelo }}
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="curso" id="curso" value="{{ old('curso') }}">
                    <input type="hidden" name="paralelo" id="paralelo" value="{{ old('paralelo') }}">
                </div>

                <div class="col-md-6 form-group">
                    <label>Título <span class="text-danger">*</span></label>
                    <input type="text" name="titulo" class="form-control"
                           value="{{ old('titulo') }}" maxlength="150" required>
                </div>

                <div class="col-md-4 form-group">
                    <label>Fecha <span class="text-danger">*</span></label>
                    <input type="date" name="fecha" class="form-control"
                           value="{{ old('fecha') }}" required>
                </div>

                <div class="col-md-4 form-group">
                    <label>Hora</label>
                    <input type="time" name="hora" class="form-control"
                           value="{{ old('hora') }}">
                </div>

                <div class="col-md-12 form-group mb-0">
                    <label>Observación</label>
                    <textarea name="observacion" class="form-control" rows="2"
                              maxlength="500">{{ old('observacion') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card-footer text-right">
            <a href="{{ route('docente.examenes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Guardar examen
            </button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
    <script src="{{ asset('js/examenes-docente.js') }}"></script>
@endpush