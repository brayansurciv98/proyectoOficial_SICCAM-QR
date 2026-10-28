@extends('layouts.app')

@section('title', 'Registrar Docente - SICCAM QR')
@section('page_title', 'Registrar Docente')

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

<form action="{{ route('docentes.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-md-6">
            <div class="card card-outline card-success">
                <div class="card-header"><strong>Datos del Docente</strong></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Nombres *</label>
                        <input type="text" name="nombres" class="form-control" value="{{ old('nombres') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Apellidos *</label>
                        <input type="text" name="apellidos" class="form-control" value="{{ old('apellidos') }}" required>
                    </div>
                    <div class="form-group">
                        <label>CI</label>
                        <input type="text" name="ci" class="form-control" value="{{ old('ci') }}" inputmode="numeric" maxlength="10" pattern="[0-9]{5,10}" title="Entre 5 y 10 números">
                        <small class="form-text text-muted">CI o correo obligatorio: el docente los usa para iniciar sesión.</small>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" class="form-control" value="{{ old('telefono') }}" inputmode="numeric" maxlength="8" pattern="[0-9]{7,8}" title="7 u 8 números">
                    </div>
                    <div class="form-group">
                        <label>Materia *</label>
                        <select name="materia_id" class="form-control" required>
                            <option value="">Seleccione...</option>
                            @foreach($materias as $m)
                                <option value="{{ $m->id }}" {{ old('materia_id') == $m->id ? 'selected' : '' }}>
                                    {{ $m->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
    <div class="card card-outline card-success">
        <div class="card-header">
            <strong>Cursos asignados *</strong>
        </div>

        <div class="card-body">
            {{-- Selector para agregar --}}
            <div class="row mb-3">
                <div class="col-5">
                    <select id="cursoSelect" class="form-control">
                        <option value="">Curso</option>
                        @for($i = 1; $i <= 6; $i++)
                            <option value="{{ $i }}">{{ $i }}°</option>
                        @endfor
                    </select>
                </div>
                <div class="col-5">
                    <select id="paraleloSelect" class="form-control">
                        <option value="">Paralelo</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                    </select>
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-success btn-block" id="btnAddCurso">+</button>
                </div>
            </div>

            {{-- Lista acumulada --}}
            <div id="cursosAsignadosList">
                <p class="text-muted mb-0" id="cursosEmptyMsg">Aún no hay cursos asignados.</p>
            </div>

            {{-- Inputs hidden que sí se envían al backend --}}
            <div id="cursosHiddenInputs"></div>
        </div>
    </div>

    <button type="submit" class="btn btn-success btn-lg btn-block mt-3">
        <i class="fas fa-save"></i> Registrar Docente
    </button>
    <a href="{{ route('docentes.index') }}" class="btn btn-outline-secondary btn-block">Cancelar</a>
</div>
    </div>
</form>
@endsection


@push('scripts')
    <script>
        window.cursosInicialesDocente = [];
    </script>
    <script src="{{ asset('js/docentes-form.js') }}"></script>
@endpush