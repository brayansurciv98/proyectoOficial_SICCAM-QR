@extends('layouts.app')

@section('title', 'Nuevo Horario - SICCAM QR')
@section('page_title', 'Registrar Horario')

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

<form method="POST" action="{{ route('horarios.store') }}">
    @csrf
    <div class="card card-outline card-success shadow-sm">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 form-group">
                    <label>Día <span class="text-danger">*</span></label>
                    <select name="dia" class="form-control" required>
                        <option value="">Seleccione...</option>
                        @foreach([1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes'] as $num => $nom)
                            <option value="{{ $num }}" {{ old('dia') == $num ? 'selected' : '' }}>{{ $nom }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 form-group">
                    <label>Bloque <span class="text-danger">*</span></label>
                    <select name="bloque" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <option value="1" {{ old('bloque') == 1 ? 'selected' : '' }}>1ª (13:30 - 15:00)</option>
                        <option value="2" {{ old('bloque') == 2 ? 'selected' : '' }}>2ª (15:10 - 16:40)</option>
                        <option value="3" {{ old('bloque') == 3 ? 'selected' : '' }}>3ª (16:50 - 17:50)</option>
                        <option value="4" {{ old('bloque') == 4 ? 'selected' : '' }}>4ª (17:50 - 18:45)</option>
                    </select>
                </div>

                <div class="col-md-2 form-group">
                    <label>Curso <span class="text-danger">*</span></label>
                    <select name="curso" class="form-control" required>
                        <option value="">—</option>
                        @for($i = 1; $i <= 6; $i++)
                            <option value="{{ $i }}" {{ old('curso') == $i ? 'selected' : '' }}>{{ $i }}°</option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-2 form-group">
                    <label>Paralelo <span class="text-danger">*</span></label>
                    <select name="paralelo" class="form-control" required>
                        <option value="">—</option>
                        @foreach(['A','B','C'] as $p)
                            <option value="{{ $p }}" {{ old('paralelo') == $p ? 'selected' : '' }}>{{ $p }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 form-group">
                    <label>Docente <span class="text-danger">*</span></label>
                    <select name="docente_id" id="docente_id" class="form-control" required>
                        <option value="">Seleccione...</option>
                        @foreach($docentes as $d)
                            <option value="{{ $d->id }}"
                                    data-materia="{{ $d->materia_id }}"
                                    data-materia-nombre="{{ $d->materia->nombre ?? '' }}"
                                    {{ old('docente_id') == $d->id ? 'selected' : '' }}>
                                {{ $d->apellidos }}, {{ $d->nombres }}
                                @if($d->materia) — {{ $d->materia->nombre }} @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 form-group">
                    <label>Materia <span class="text-danger">*</span></label>
                    <input type="text" id="materia_nombre" class="form-control" readonly
                           placeholder="Se asigna según el docente"
                           value="">
                    <input type="hidden" name="materia_id" id="materia_id" value="{{ old('materia_id') }}">
                </div>
            </div>
        </div>
        <div class="card-footer text-right">
            <a href="{{ route('horarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Guardar horario
            </button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
    <script src="{{ asset('js/horarios-form.js') }}"></script>
@endpush