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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-journal-bookmark mr-1"></i> Cursos asignados *</strong>
                    <button type="button" class="btn btn-sm btn-outline-success" id="btnAddCurso">
                        + Agregar
                    </button>
                </div>
                <div class="card-body" id="cursosContainer"
                    data-curso-index="{{ count($cursosOld) }}"></div>
                    @php
                        $cursosOld = old('cursos');
                        if (!$cursosOld) {
                            $cursosOld = $docente->cursos->map(fn ($c) => [
                                'curso' => $c->curso,
                                'paralelo' => $c->paralelo,
                            ])->values()->all();
                        }
                        if (empty($cursosOld)) {
                            $cursosOld = [['curso' => '', 'paralelo' => '']];
                        }
                    @endphp

                    @foreach($cursosOld as $i => $item)
                        <div class="row curso-item mb-2">
                            <div class="col-5">
                                <select name="cursos[{{ $i }}][curso]" class="form-control" required>
                                    <option value="">Curso</option>
                                    @for($n = 1; $n <= 6; $n++)
                                        <option value="{{ $n }}" {{ (string)($item['curso'] ?? '') === (string)$n ? 'selected' : '' }}>
                                            {{ $n }}°
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-5">
                                <select name="cursos[{{ $i }}][paralelo]" class="form-control" required>
                                    <option value="">Paralelo</option>
                                    @foreach(['A','B','C'] as $p)
                                        <option value="{{ $p }}" {{ ($item['paralelo'] ?? '') == $p ? 'selected' : '' }}>
                                            {{ $p }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-2">
                                <button type="button"
                                        class="btn btn-outline-danger btn-block btn-remove"
                                        {{ $i === 0 ? 'disabled' : '' }}>×</button>
                            </div>
                        </div>
                    @endforeach
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
    <script src="{{ asset('js/docentes-form.js') }}"></script>
@endpush