@extends('layouts.app')

@section('title', 'Asistencias - SICCAM QR')
@section('page_title', 'Gestión de Asistencias')

@section('content')
<!-- Encabezado con botones de acción -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1 font-weight-bold text-dark">Registro de Asistencias</h5>
        <small class="text-muted">Hoy: {{ date('d/m/Y') }}</small>
    </div>
    <div>
        <button class="btn btn-outline-success btn-sm mr-1">
            <i class="bi bi-file-earmark-excel mr-1"></i> Exportar Excel
        </button>
        <button class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-earmark-pdf mr-1"></i> Exportar PDF
        </button>
    </div>
</div>

<!-- Card de Filtros -->
<div class="card card-outline card-success shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label small text-muted font-weight-bold">Fecha</label>
                <input type="date" class="form-control" value="{{ date('Y-m-d') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted font-weight-bold">Curso</label>
                <select class="form-control custom-select">
                    <option selected>Todos los cursos</option>
                    <option>1ro Secundaria</option>
                    <option>2do Secundaria</option>
                    <option>3ro Secundaria</option>
                    <option>4to Secundaria</option>
                    <option>5to Secundaria</option>
                    <option>6to Secundaria</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted font-weight-bold">Estado</label>
                <select class="form-control custom-select">
                    <option selected>Todos</option>
                    <option>Presente</option>
                    <option>Tardanza</option>
                    <option>Ausente</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted font-weight-bold">Buscar estudiante</label>
                <input type="text" class="form-control" placeholder="Nombre o código...">
            </div>
        </div>
    </div>
</div>

<!-- Tabla de asistencias -->
<div class="card card-outline card-success shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead style="background-color: #e8f5e9;">
                    <tr>
                        <th class="pl-4">Código</th>
                        <th>Estudiante</th>
                        <th>Curso</th>
                        <th>Fecha</th>
                        <th>Hora Ingreso</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="pl-4 font-weight-bold">EST-001245</td>
                        <td>Juan Carlos Mendoza López</td>
                        <td>4to Secundaria - A</td>
                        <td>28/07/2026</td>
                        <td>07:48:12</td>
                        <td><span class="badge badge-success px-2 py-1">Presente</span></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" title="Ver detalle"><i class="bi bi-eye"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td class="pl-4 font-weight-bold">EST-001246</td>
                        <td>María Fernanda Quispe Rojas</td>
                        <td>5to Secundaria - B</td>
                        <td>28/07/2026</td>
                        <td>08:12:45</td>
                        <td><span class="badge badge-warning px-2 py-1">Tardanza</span></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" title="Ver detalle"><i class="bi bi-eye"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td class="pl-4 font-weight-bold">EST-001247</td>
                        <td>Carlos Andrés Mamani Vásquez</td>
                        <td>3ro Secundaria - A</td>
                        <td>28/07/2026</td>
                        <td>—</td>
                        <td><span class="badge badge-danger px-2 py-1">Ausente</span></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" title="Ver detalle"><i class="bi bi-eye"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td class="pl-4 font-weight-bold">EST-001248</td>
                        <td>Ana Lucía Torrez Gutiérrez</td>
                        <td>6to Secundaria - A</td>
                        <td>28/07/2026</td>
                        <td>07:35:02</td>
                        <td><span class="badge badge-success px-2 py-1">Presente</span></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" title="Ver detalle"><i class="bi bi-eye"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td class="pl-4 font-weight-bold">EST-001249</td>
                        <td>Diego Alejandro Flores Choque</td>
                        <td>2do Secundaria - B</td>
                        <td>28/07/2026</td>
                        <td>07:52:18</td>
                        <td><span class="badge badge-success px-2 py-1">Presente</span></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" title="Ver detalle"><i class="bi bi-eye"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted">Mostrando 5 registros del día</small>
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item disabled"><a class="page-link" href="#">Anterior</a></li>
            <li class="page-item active"><a class="page-link" href="#">1</a></li>
            <li class="page-item"><a class="page-link" href="#">2</a></li>
            <li class="page-item"><a class="page-link" href="#">Siguiente</a></li>
        </ul>
    </div>
</div>
@endsection