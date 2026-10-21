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

<form action="{{ route('examenes.store') }}" method="POST"
      id="form-examen"
      data-horarios='@json($horariosJs)'
      data-examenes='@json($examenesJs)'>
    @csrf

    <div class="card card-outline card-success shadow-sm">
        <div class="card-header"><strong>Datos del examen</strong></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Título <span class="text-danger">*</span></label>
                    <input type="text" name="titulo" class="form-control"
                           value="{{ old('titulo') }}" required maxlength="150"
                           placeholder="Ej: Primer parcial">
                </div>

                <div class="col-md-3 form-group">
                    <label>Fecha <span class="text-danger">*</span></label>
                    <input type="date" name="fecha" class="form-control"
                           value="{{ old('fecha') }}" required>
                </div>

                <div class="col-md-3 form-group">
                    <label>Hora</label>
                    <input type="time" name="hora" class="form-control"
                           value="{{ old('hora') }}">
                </div>

                <div class="col-md-6 form-group">
                    <label>Docente <span class="text-danger">*</span></label>
                    <select name="docente_id" id="docente_id" class="form-control" required>
                        <option value="">Seleccione...</option>
                        @foreach($docentes as $d)
                            <option value="{{ $d->id }}"
                                    data-materia="{{ $d->materia_id }}"
                                    {{ old('docente_id') == $d->id ? 'selected' : '' }}>
                                {{ $d->apellidos }}, {{ $d->nombres }}
                                ({{ $d->materia->nombre ?? 'Sin materia' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 form-group">
                    <label>Materia <span class="text-danger">*</span></label>
                    <select name="materia_id" id="materia_id" class="form-control" required>
                        <option value="">Seleccione...</option>
                        @foreach($materias as $m)
                            <option value="{{ $m->id }}" {{ old('materia_id') == $m->id ? 'selected' : '' }}>
                                {{ $m->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-12 form-group mt-n2">
                    <small id="ayuda-docente-materia" class="text-muted"></small>
                    <button type="button" id="limpiar-docente-materia" class="btn btn-link btn-sm p-0 ml-2 align-baseline">
                        Limpiar
                    </button>
                </div>

                <div class="col-md-3 form-group">
                    <label>Curso <span class="text-danger">*</span></label>
                    <select name="curso" class="form-control" required>
                        <option value="">—</option>
                        @for($i = 1; $i <= 6; $i++)
                            <option value="{{ $i }}" {{ old('curso') == $i ? 'selected' : '' }}>{{ $i }}°</option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-3 form-group">
                    <label>Paralelo <span class="text-danger">*</span></label>
                    <select name="paralelo" class="form-control" required>
                        <option value="">—</option>
                        <option value="A" {{ old('paralelo') == 'A' ? 'selected' : '' }}>A</option>
                        <option value="B" {{ old('paralelo') == 'B' ? 'selected' : '' }}>B</option>
                        <option value="C" {{ old('paralelo') == 'C' ? 'selected' : '' }}>C</option>
                    </select>
                </div>

                <div class="col-md-12 form-group mb-0">
                    <label>Observación</label>
                    <textarea name="observacion" class="form-control" rows="2"
                              maxlength="500">{{ old('observacion') }}</textarea>
                </div>
            </div>
        </div>
        <div class="card-footer text-right">
            <a href="{{ route('examenes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Guardar examen
            </button>
        </div>
    </div>

    {{-- Guía: horario semanal del docente elegido (lo llena public/js/examenes-guia-horario.js) --}}
    <div class="card card-outline card-success shadow-sm" id="guia-horario">
        <div class="card-header d-flex flex-wrap align-items-center">
            <strong class="mr-2"><i class="bi bi-calendar-week text-success"></i> Horario del docente</strong>
            <small class="text-muted" id="guia-titulo"></small>
        </div>
        <div class="card-body">
            <p class="text-muted mb-0" id="guia-mensaje">Elija un docente (o una materia) para ver en qué días y bloques tiene clase con cada curso.</p>

            <div class="d-none" id="guia-contenido">
                <div class="table-responsive">
                    <table class="table table-bordered text-center mb-2" style="min-width: 680px;" id="guia-tabla">
                        <thead>
                            <tr>
                                <th style="width: 110px;"></th>
                                @foreach(range(1, 4) as $bloque)
                                    @php [$inicio, $fin] = \App\Models\Horario::horasBloque($bloque); @endphp
                                    <th class="align-middle">
                                        {{ $bloque }}° BLOQUE
                                        <span class="font-weight-normal d-block small">{{ substr($inicio, 0, 5) }} – {{ substr($fin, 0, 5) }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(range(1, 5) as $dia)
                                <tr data-dia="{{ $dia }}">
                                    <th class="align-middle font-weight-normal">{{ \App\Models\Horario::nombreDia($dia) }}</th>
                                    @foreach(range(1, 4) as $bloque)
                                        <td class="align-middle p-1" style="height: 70px;" data-dia="{{ $dia }}" data-bloque="{{ $bloque }}"></td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <small class="text-muted d-block mb-2">
                    Al elegir curso y paralelo se resaltan en verde sus clases con ese curso.
                </small>
                <div id="guia-aviso"></div>
                <div id="guia-fechas"></div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
    <script src="{{ asset('js/examenes-form.js') }}"></script>
    <script src="{{ asset('js/examenes-guia-horario.js') }}"></script>
@endpush