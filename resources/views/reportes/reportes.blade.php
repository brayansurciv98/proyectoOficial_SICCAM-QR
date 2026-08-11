@extends('layouts.app')

@section('title', 'Reportes - SICCAM QR')
@section('page_title', 'Reportes de Asistencia')

@section('content')
<!-- Encabezado con acciones -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1 font-weight-bold text-dark">Reportes de Asistencia</h5>
        <small class="text-muted">Generación y consulta de reportes detallados</small>
    </div>
</div>

<!-- Card de Filtros del reporte -->
<div class="card card-outline card-success shadow-sm mb-4">
    <div class="card-header">
        <h3 class="card-title font-weight-bold text-dark"><i class="bi bi-funnel mr-1"></i> Filtros del reporte</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label small text-muted font-weight-bold">Fecha desde</label>
                <input type="date" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted font-weight-bold">Fecha hasta</label>
                <input type="date" class="form-control">
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
                <label class="form-label small text-muted font-weight-bold">Tipo de reporte</label>
                <select class="form-control custom-select">
                    <option selected>Resumen general</option>
                    <option>Por estudiante</option>
                    <option>Por curso</option>
                    <option>Tardanzas</option>
                    <option>Ausencias</option>
                </select>
            </div>
        </div>
        <div class="mt-3 d-flex">
            <button class="btn btn-success btn-sm mr-2">
                <i class="bi bi-search mr-1"></i> Generar reporte
            </button>
            <button class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-clockwise mr-1"></i> Limpiar
            </button>
        </div>
    </div>
</div>

<!-- Tarjetas de métricas (Info-boxes de AdminLTE) -->
<div class="row mb-4">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm mb-3">
            <span class="info-box-icon bg-success elevation-1"><i class="bi bi-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Presentes</span>
                <span class="info-box-number h4 mb-0 font-weight-bold">1,105</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm mb-3">
            <span class="info-box-icon bg-warning text-white elevation-1"><i class="bi bi-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Tardanzas</span>
                <span class="info-box-number h4 mb-0 font-weight-bold">87</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm mb-3">
            <span class="info-box-icon bg-danger elevation-1"><i class="bi bi-x-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Ausencias</span>
                <span class="info-box-number h4 mb-0 font-weight-bold">56</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm mb-3">
            <span class="info-box-icon bg-primary elevation-1"><i class="bi bi-percent"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">% Asistencia</span>
                <span class="info-box-number h4 mb-0 font-weight-bold">94.2%</span>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Resultados del Reporte -->
<div class="card card-outline card-success shadow-sm">
    <div class="card-header border-0 d-flex justify-content-between align-items-center py-3">
        <h3 class="card-title font-weight-bold text-dark m-0">Resultados del reporte</h3>
        <div class="card-tools m-0">
            <button class="btn btn-outline-success btn-sm mr-1">
                <i class="bi bi-file-earmark-excel mr-1"></i> Excel
            </button>
            <button class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf mr-1"></i> PDF
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead style="background-color: #e8f5e9;">
                    <tr>
                        <th class="pl-4">Curso</th>
                        <th>Estudiantes</th>
                        <th>Presentes</th>
                        <th>Tardanzas</th>
                        <th>Ausencias</th>
                        <th>% Asistencia</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="pl-4 font-weight-bold">1ro Secundaria</td>
                        <td>210</td>
                        <td>198</td>
                        <td>7</td>
                        <td>5</td>
                        <td><strong class="text-success">94.3%</strong></td>
                    </tr>
                    <tr>
                        <td class="pl-4 font-weight-bold">2do Secundaria</td>
                        <td>205</td>
                        <td>192</td>
                        <td>8</td>
                        <td>5</td>
                        <td><strong class="text-success">93.7%</strong></td>
                    </tr>
                    <tr>
                        <td class="pl-4 font-weight-bold">3ro Secundaria</td>
                        <td>198</td>
                        <td>185</td>
                        <td>9</td>
                        <td>4</td>
                        <td><strong class="text-success">93.4%</strong></td>
                    </tr>
                    <tr>
                        <td class="pl-4 font-weight-bold">4to Secundaria</td>
                        <td>215</td>
                        <td>203</td>
                        <td>6</td>
                        <td>6</td>
                        <td><strong class="text-success">94.4%</strong></td>
                    </tr>
                    <tr>
                        <td class="pl-4 font-weight-bold">5to Secundaria</td>
                        <td>208</td>
                        <td>195</td>
                        <td>10</td>
                        <td>3</td>
                        <td><strong class="text-success">93.8%</strong></td>
                    </tr>
                    <tr>
                        <td class="pl-4 font-weight-bold">6to Secundaria</td>
                        <td>212</td>
                        <td>202</td>
                        <td>7</td>
                        <td>3</td>
                        <td><strong class="text-success">95.3%</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection