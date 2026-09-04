@extends('layouts.app')

@section('title', 'Registrar Estudiante - SICCAM QR')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-user-plus text-success"></i>
            Registrar Estudiante + Tutor
        </h1>
        <a href="{{ route('estudiantes.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
@stop

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

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

<form action="{{ route('estudiantes.store') }}" method="POST" id="formRegistro">
    @csrf

    <div class="row">
        {{-- TUTOR --}}
        <div class="col-md-6">
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-friends"></i> Datos del Tutor / Padre de Familia
                    </h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="tutor_nombres">Nombres del Tutor <span class="text-danger">*</span></label>
                        <input type="text" name="tutor_nombres" id="tutor_nombres"
                               class="form-control @error('tutor_nombres') is-invalid @enderror"
                               value="{{ old('tutor_nombres') }}"
                               pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+$"
                               title="Solo letras y espacios"
                               required>
                        @error('tutor_nombres') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="tutor_apellidos">Apellidos del Tutor <span class="text-danger">*</span></label>
                        <input type="text" name="tutor_apellidos" id="tutor_apellidos"
                               class="form-control @error('tutor_apellidos') is-invalid @enderror"
                               value="{{ old('tutor_apellidos') }}"
                               pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+$"
                               title="Solo letras y espacios"
                               required>
                        @error('tutor_apellidos') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="tutor_ci">Cédula de Identidad</label>
                        <input type="text" name="tutor_ci" id="tutor_ci"
                               class="form-control @error('tutor_ci') is-invalid @enderror"
                               value="{{ old('tutor_ci') }}"
                               inputmode="numeric"
                               pattern="^[0-9]+$"
                               title="Solo números"
                               placeholder="Ej: 9894100">
                        @error('tutor_ci') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="tutor_telefono">Teléfono / Celular</label>
                        <input type="text" name="tutor_telefono" id="tutor_telefono"
                               class="form-control @error('tutor_telefono') is-invalid @enderror"
                               value="{{ old('tutor_telefono') }}"
                               inputmode="numeric"
                               pattern="^[0-9]+$"
                               title="Solo números"
                               placeholder="Ej: 70012345">
                        @error('tutor_telefono') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="tutor_email">Correo electrónico</label>
                        <input type="email" name="tutor_email" id="tutor_email"
                               class="form-control @error('tutor_email') is-invalid @enderror"
                               value="{{ old('tutor_email') }}">
                        @error('tutor_email') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="parentesco">Parentesco</label>
                        <select name="parentesco" id="parentesco" class="form-control">
                            <option value="Padre" {{ old('parentesco') == 'Padre' ? 'selected' : '' }}>Padre</option>
                            <option value="Madre" {{ old('parentesco') == 'Madre' ? 'selected' : '' }}>Madre</option>
                            <option value="Tutor" {{ old('parentesco') == 'Tutor' ? 'selected' : '' }}>Tutor</option>
                            <option value="Abuelo/a" {{ old('parentesco') == 'Abuelo/a' ? 'selected' : '' }}>Abuelo/a</option>
                            <option value="Otro" {{ old('parentesco') == 'Otro' ? 'selected' : '' }}>Otro</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- ESTUDIANTE --}}
        <div class="col-md-6">
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-graduate"></i> Datos del Estudiante
                    </h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="nombres">Nombres <span class="text-danger">*</span></label>
                        <input type="text" name="nombres" id="nombres"
                               class="form-control @error('nombres') is-invalid @enderror"
                               value="{{ old('nombres') }}"
                               pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+$"
                               title="Solo letras y espacios"
                               required>
                        @error('nombres') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="apellidos">Apellidos <span class="text-danger">*</span></label>
                        <input type="text" name="apellidos" id="apellidos"
                               class="form-control @error('apellidos') is-invalid @enderror"
                               value="{{ old('apellidos') }}"
                               pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+$"
                               title="Solo letras y espacios"
                               required>
                        @error('apellidos') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="ci">Cédula de Identidad</label>
                        <input type="text" name="ci" id="ci"
                               class="form-control @error('ci') is-invalid @enderror"
                               value="{{ old('ci') }}"
                               inputmode="numeric"
                               pattern="^[0-9]+$"
                               title="Solo números">
                        @error('ci') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                        <input type="date" name="fecha_nacimiento" id="fecha_nacimiento"
                               class="form-control @error('fecha_nacimiento') is-invalid @enderror"
                               value="{{ old('fecha_nacimiento') }}">
                        @error('fecha_nacimiento') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="genero">Género</label>
                        <select name="genero" id="genero" class="form-control">
                            <option value="">Seleccione...</option>
                            <option value="M" {{ old('genero') == 'M' ? 'selected' : '' }}>Masculino</option>
                            <option value="F" {{ old('genero') == 'F' ? 'selected' : '' }}>Femenino</option>
                            <option value="Otro" {{ old('genero') == 'Otro' ? 'selected' : '' }}>Otro</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-7">
                            <div class="form-group">
                                <label for="curso">Curso <span class="text-danger">*</span></label>
                                <input type="number" name="curso" id="curso"
                                       class="form-control @error('curso') is-invalid @enderror"
                                       value="{{ old('curso') }}"
                                       min="1" max="6" step="1"
                                       inputmode="numeric"
                                       placeholder="1 a 6"
                                       required>
                                @error('curso')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="text-muted">Secundaria: solo del 1 al 6</small>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="paralelo">Paralelo</label>
                                <select name="paralelo" id="paralelo"
                                        class="form-control @error('paralelo') is-invalid @enderror">
                                    <option value="">Seleccione...</option>
                                    <option value="A" {{ old('paralelo', $estudiante->paralelo ?? '') == 'A' ? 'selected' : '' }}>A</option>
                                    <option value="B" {{ old('paralelo', $estudiante->paralelo ?? '') == 'B' ? 'selected' : '' }}>B</option>
                                    <option value="C" {{ old('paralelo', $estudiante->paralelo ?? '') == 'C' ? 'selected' : '' }}>C</option>
                                </select>
                                @error('paralelo')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12 text-center">
            <button type="submit" class="btn btn-success btn-lg px-5">
                <i class="fas fa-save"></i> Registrar Estudiante y Generar QR
            </button>
            <a href="{{ route('estudiantes.index') }}" class="btn btn-outline-secondary btn-lg ml-2">
                Cancelar
            </a>
        </div>
    </div>
</form>


@stop

@section('css')
<style>
    .card-success.card-outline { border-top: 3px solid #2e7d32; }
    .btn-success { background-color: #1a5f2a; border-color: #1a5f2a; }
    .btn-success:hover { background-color: #2e7d32; border-color: #2e7d32; }
    .text-success { color: #1a5f2a !important; }
</style>
@stop

@push('scripts')
    <script src="{{ asset('js/validaciones-estudiante.js') }}"></script>
@endpush