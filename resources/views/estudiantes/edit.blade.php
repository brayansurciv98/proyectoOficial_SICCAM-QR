@extends('layouts.app')

@section('title', 'Editar Estudiante - SICCAM QR')
@section('page_title', 'Editar Estudiante')

@section('content')

<!-- Encabezado con navegación -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1 font-weight-bold text-dark">
            <i class="bi bi-pencil-square text-success mr-2"></i> Editar Expediente de Estudiante
        </h5>
        <small class="text-muted">Actualización de datos personales, tutor y credencial QR</small>
    </div>
    <div>
        <a href="{{ route('estudiantes.show', $estudiante) }}" class="btn btn-outline-secondary btn-sm mr-1">
            <i class="bi bi-eye"></i> Ver Perfil
        </a>
        <a href="{{ route('estudiantes.index') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-arrow-left"></i> Volver al listado
        </a>
    </div>
</div>

<form action="{{ route('estudiantes.update', $estudiante) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="row">

        {{-- ===================== COLUMNA IZQUIERDA: DATOS DEL ESTUDIANTE ===================== --}}
        <div class="col-md-8">
            <div class="card card-success card-outline shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold text-dark">
                        <i class="fas fa-user-graduate mr-1 text-success"></i> Datos Personales del Estudiante
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Código del Estudiante (Deshabilitado/Lectura si es generado automáticamente) -->
                        <div class="col-md-6 form-group">
                            <label for="codigo_estudiante" class="font-weight-bold text-dark">Código de Estudiante</label>
                            <input type="text" class="form-control bg-light" id="codigo_estudiante" value="{{ $estudiante->codigo_estudiante }}" readonly>
                            <small class="form-text text-muted">El código identificador único no se puede modificar.</small>
                        </div>

                        <!-- Cédula de Identidad -->
                        <div class="col-md-6 form-group">
                            <label for="ci" class="font-weight-bold text-dark">Cédula de Identidad (CI)</label>
                            <input type="text" name="ci" id="ci" class="form-control @error('ci') is-invalid @enderror" value="{{ old('ci', $estudiante->ci) }}" placeholder="Ej. 8765432">
                            @error('ci')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Nombres -->
                        <div class="col-md-6 form-group">
                            <label for="nombres" class="font-weight-bold text-dark">Nombres <span class="text-danger">*</span></label>
                            <input type="text" name="nombres" id="nombres" class="form-control @error('nombres') is-invalid @enderror" value="{{ old('nombres', $estudiante->nombres) }}" required>
                            @error('nombres')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Apellidos -->
                        <div class="col-md-6 form-group">
                            <label for="apellidos" class="font-weight-bold text-dark">Apellidos <span class="text-danger">*</span></label>
                            <input type="text" name="apellidos" id="apellidos" class="form-control @error('apellidos') is-invalid @enderror" value="{{ old('apellidos', $estudiante->apellidos) }}" required>
                            @error('apellidos')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Curso -->
                        <div class="col-md-4 form-group">
                            <label for="curso" class="font-weight-bold text-dark">Curso / Grado <span class="text-danger">*</span></label>
                            <input type="text" name="curso" id="curso" class="form-control @error('curso') is-invalid @enderror" value="{{ old('curso', $estudiante->curso) }}" placeholder="Ej. 1º Secundaria" required>
                            @error('curso')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Paralelo -->
                        <div class="col-md-4 form-group">
                            <label for="paralelo" class="font-weight-bold text-dark">Paralelo</label>
                            <input type="text" name="paralelo" id="paralelo" class="form-control @error('paralelo') is-invalid @enderror" value="{{ old('paralelo', $estudiante->paralelo) }}" placeholder="Ej. A">
                            @error('paralelo')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Género -->
                        <div class="col-md-4 form-group">
                            <label for="genero" class="font-weight-bold text-dark">Género</label>
                            <select name="genero" id="genero" class="form-control @error('genero') is-invalid @enderror">
                                <option value="">-- Seleccionar --</option>
                                <option value="M" {{ old('genero', $estudiante->genero) == 'M' ? 'selected' : '' }}>Masculino</option>
                                <option value="F" {{ old('genero', $estudiante->genero) == 'F' ? 'selected' : '' }}>Femenino</option>
                            </select>
                            @error('genero')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Fecha de Nacimiento -->
                        <div class="col-md-6 form-group mb-0">
                            <label for="fecha_nacimiento" class="font-weight-bold text-dark">Fecha de Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" class="form-control @error('fecha_nacimiento') is-invalid @enderror" value="{{ old('fecha_nacimiento', $estudiante->fecha_nacimiento) }}">
                            @error('fecha_nacimiento')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Estado -->
                        <div class="col-md-6 form-group mb-0">
                            <label for="estado" class="font-weight-bold text-dark">Estado del Estudiante <span class="text-danger">*</span></label>
                            <select name="estado" id="estado" class="form-control @error('estado') is-invalid @enderror" required>
                                <option value="activo" {{ old('estado', $estudiante->estado) == 'activo' ? 'selected' : '' }}>Activo</option>
                                <option value="inactivo" {{ old('estado', $estudiante->estado) == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                                <option value="retirado" {{ old('estado', $estudiante->estado) == 'retirado' ? 'selected' : '' }}>Retirado</option>
                            </select>
                            @error('estado')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Datos del Tutor Asignado -->
            @php $tutor = $estudiante->tutores->first(); @endphp
            <div class="card card-success card-outline shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold text-dark">
                        <i class="fas fa-user-friends mr-1 text-success"></i> Datos del Tutor / Padre de Familia
                    </h3>
                </div>
                <div class="card-body">
                    <input type="hidden" name="tutor_id" value="{{ $tutor->id ?? '' }}">
                    <div class="row">
                        <!-- Nombres del Tutor -->
                        <div class="col-md-6 form-group">
                            <label for="tutor_nombres" class="font-weight-bold text-dark">Nombres del Tutor</label>
                            <input type="text" name="tutor_nombres" id="tutor_nombres" class="form-control @error('tutor_nombres') is-invalid @enderror" value="{{ old('tutor_nombres', $tutor->nombres ?? '') }}">
                            @error('tutor_nombres')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Apellidos del Tutor -->
                        <div class="col-md-6 form-group">
                            <label for="tutor_apellidos" class="font-weight-bold text-dark">Apellidos del Tutor</label>
                            <input type="text" name="tutor_apellidos" id="tutor_apellidos" class="form-control @error('tutor_apellidos') is-invalid @enderror" value="{{ old('tutor_apellidos', $tutor->apellidos ?? '') }}">
                            @error('tutor_apellidos')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Parentesco -->
                        <div class="col-md-4 form-group">
                            <label for="tutor_parentesco" class="font-weight-bold text-dark">Parentesco</label>
                            <select name="tutor_parentesco" id="tutor_parentesco" class="form-control @error('tutor_parentesco') is-invalid @enderror">
                                @php $parentescoActual = $tutor->pivot->parentesco ?? $tutor->parentesco ?? ''; @endphp
                                <option value="Padre" {{ old('tutor_parentesco', $parentescoActual) == 'Padre' ? 'selected' : '' }}>Padre</option>
                                <option value="Madre" {{ old('tutor_parentesco', $parentescoActual) == 'Madre' ? 'selected' : '' }}>Madre</option>
                                <option value="Tutor Legal" {{ old('tutor_parentesco', $parentescoActual) == 'Tutor Legal' ? 'selected' : '' }}>Tutor Legal</option>
                                <option value="Otro" {{ old('tutor_parentesco', $parentescoActual) == 'Otro' ? 'selected' : '' }}>Otro</option>
                            </select>
                        </div>

                        <!-- Teléfono / WhatsApp -->
                        <div class="col-md-4 form-group">
                            <label for="tutor_telefono" class="font-weight-bold text-dark">Teléfono / Celular</label>
                            <input type="text" name="tutor_telefono" id="tutor_telefono" class="form-control @error('tutor_telefono') is-invalid @enderror" value="{{ old('tutor_telefono', $tutor->telefono ?? '') }}" placeholder="Ej. 76543210">
                            @error('tutor_telefono')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Correo Electrónico -->
                        <div class="col-md-4 form-group">
                            <label for="tutor_email" class="font-weight-bold text-dark">Correo Electrónico</label>
                            <input type="email" name="tutor_email" id="tutor_email" class="form-control @error('tutor_email') is-invalid @enderror" value="{{ old('tutor_email', $tutor->email ?? '') }}" placeholder="ejemplo@correo.com">
                            @error('tutor_email')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================== COLUMNA DERECHA: QR Y ACCIONES ===================== --}}
        <div class="col-md-4">
            
            <!-- Estado actual del QR -->
            <div class="card card-success card-outline shadow-sm text-center mb-4">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold text-dark float-none">
                        <i class="bi bi-qr-code-scan mr-1 text-success"></i> Estado del Código QR
                    </h3>
                </div>
                <div class="card-body">
                    <div class="p-3 bg-light rounded d-inline-block border mb-3">
                        @if($estudiante->qr_imagen)
                            <img src="{{ asset('storage/'.$estudiante->qr_imagen) }}" 
                                 alt="Código QR de {{ $estudiante->nombres }}" 
                                 class="img-fluid" 
                                 style="max-width: 160px; height: auto;">
                        @else
                            <div class="text-muted p-3">
                                <i class="bi bi-qr-code display-4 d-block mb-1 text-secondary"></i>
                                <small>Sin imagen QR generada</small>
                            </div>
                        @endif
                    </div>

                    <!-- Casilla para Regenerar QR -->
                    <div class="custom-control custom-checkbox text-left mt-2">
                        <input type="checkbox" class="custom-control-input" id="regenerar_qr" name="regenerar_qr" value="1">
                        <label class="custom-control-label text-dark font-weight-bold" for="regenerar_qr">
                            Regenerar Código QR
                        </label>
                        <small class="form-text text-muted">Marca esta casilla si deseas volver a generar la imagen del código QR con los datos actualizados.</small>
                    </div>
                </div>
            </div>

            <!-- Card de Guardar Cambios -->
            <div class="card card-success card-outline shadow-sm">
                <div class="card-body">
                    <button type="submit" class="btn btn-success btn-block font-weight-bold py-2 mb-2">
                        <i class="bi bi-check-lg mr-1"></i> Guardar Cambios
                    </button>
                    <a href="{{ route('estudiantes.show', $estudiante) }}" class="btn btn-outline-secondary btn-block">
                        <i class="bi bi-x-lg mr-1"></i> Cancelar
                    </a>
                </div>
            </div>

        </div>

    </div>
</form>

@endsection