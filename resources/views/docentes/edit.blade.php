@extends('layouts.app')

@section('title', 'Editar Docente - SICCAM QR')
@section('page_title', 'Editar Docente')

@section('content')

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <strong>Error:</strong>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

@php
    $cursosOld = old('cursos');

    if (!$cursosOld) {
        $cursosOld = $docente->cursos->map(fn ($c) => [
            'curso' => (string) $c->curso,
            'paralelo' => $c->paralelo,
        ])->values()->all();
    }

    if (empty($cursosOld)) {
        $cursosOld = [];
    }
@endphp

<form action="{{ route('docentes.update', $docente) }}" method="POST" id="formDocente">
    @csrf
    @method('PUT')

    <div class="row">
        {{-- DATOS DEL DOCENTE --}}
        <div class="col-md-6">
            <div class="card card-outline card-success shadow-sm">
                <div class="card-header">
                    <strong><i class="bi bi-person-badge mr-1"></i> Datos del Docente</strong>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="nombres">Nombres <span class="text-danger">*</span></label>
                        <input type="text" name="nombres" id="nombres"
                               class="form-control @error('nombres') is-invalid @enderror"
                               value="{{ old('nombres', $docente->nombres) }}"
                               pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+$"
                               title="Solo letras y espacios"
                               required>
                        @error('nombres')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="apellidos">Apellidos <span class="text-danger">*</span></label>
                        <input type="text" name="apellidos" id="apellidos"
                               class="form-control @error('apellidos') is-invalid @enderror"
                               value="{{ old('apellidos', $docente->apellidos) }}"
                               pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+$"
                               title="Solo letras y espacios"
                               required>
                        @error('apellidos')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="genero">Género</label>
                        @php $generoActual = old('genero', $docente->genero); @endphp
                        <select name="genero" id="genero" class="form-control @error('genero') is-invalid @enderror">
                            <option value="">Seleccione...</option>
                            <option value="M" {{ $generoActual === 'M' ? 'selected' : '' }}>Masculino</option>
                            <option value="F" {{ $generoActual === 'F' ? 'selected' : '' }}>Femenino</option>
                            <option value="Otro" {{ $generoActual === 'Otro' ? 'selected' : '' }}>Otro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="ci">CI</label>
                        <input type="text" name="ci" id="ci"
                               class="form-control @error('ci') is-invalid @enderror"
                               value="{{ old('ci', $docente->ci) }}"
                               inputmode="numeric"
                               pattern="^[0-9]+$"
                               title="Solo números">
                        @error('ci')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $docente->email) }}">
                        @error('email')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="text" name="telefono" id="telefono"
                               class="form-control @error('telefono') is-invalid @enderror"
                               value="{{ old('telefono', $docente->telefono) }}"
                               inputmode="numeric"
                               pattern="^[0-9]+$"
                               title="Solo números">
                        @error('telefono')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="materia_id">Materia <span class="text-danger">*</span></label>
                        <select name="materia_id" id="materia_id"
                                class="form-control @error('materia_id') is-invalid @enderror"
                                required>
                            <option value="">Seleccione...</option>
                            @foreach($materias as $m)
                                <option value="{{ $m->id }}"
                                    {{ old('materia_id', $docente->materia_id) == $m->id ? 'selected' : '' }}>
                                    {{ $m->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('materia_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group mb-0">
                        <label for="estado">Estado <span class="text-danger">*</span></label>
                        <select name="estado" id="estado"
                                class="form-control @error('estado') is-invalid @enderror"
                                required>
                            <option value="activo" {{ old('estado', $docente->estado) == 'activo' ? 'selected' : '' }}>Activo</option>
                            <option value="inactivo" {{ old('estado', $docente->estado) == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                        @error('estado')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- CURSOS ASIGNADOS --}}
        <div class="col-md-6">
            <div class="card card-outline card-success shadow-sm">
                <div class="card-header">
                    <strong><i class="bi bi-journal-bookmark mr-1"></i> Cursos asignados *</strong>
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
                    <div id="cursosAsignadosList"></div>

                    {{-- Inputs hidden para Laravel --}}
                    <div id="cursosHiddenInputs"></div>
                </div>
            </div>

            <button type="submit" class="btn btn-success btn-lg btn-block mt-3">
                <i class="fas fa-save"></i> Guardar cambios
            </button>
            <a href="{{ route('docentes.index') }}" class="btn btn-outline-secondary btn-block">
                Cancelar
            </a>
        </div>
    </div>
</form>
@endsection

@push('scripts')
    <div id="cursos-iniciales-data"
         data-cursos='@json($cursosOld)'
         style="display:none"></div>

    <script src="{{ asset('js/docentes-form.js') }}"></script>
@endpush