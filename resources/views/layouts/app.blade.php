<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SICCAM QR - Control de Asistencia')</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">

    @vite(['resources/js/app.js'])
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
@php
    $esAdmin = Auth::guard('web')->check();
    $esDocente = Auth::guard('docente')->check();
    $usuarioNombre = $esAdmin
        ? (Auth::guard('web')->user()->name ?? 'Administrador')
        : (Auth::guard('docente')->user()->nombres ?? 'Docente');
    $homeRoute = $esDocente ? route('docente.dashboard') : route('dashboard');
@endphp

<div class="wrapper">

    {{-- Navbar --}}
    <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom-0 shadow-sm">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <span class="nav-link fw-bold" style="color: #1a5f2a !important;">
                    <h4 class="mb-0">U.E. René Barrientos Ortuño "A"</h4>
                </span>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                    <span class="d-none d-md-inline font-weight-bold text-dark me-2">{{ $usuarioNombre }}</span>
                    <i class="bi bi-person-circle text-success fs-5"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <li class="user-header bg-success">
                        <p>
                            {{ $esAdmin ? 'Usuario Administrador' : 'Usuario Docente' }}
                            <small>SICCAM - QR</small>
                        </p>
                    </li>
                    <li class="user-footer">
                        <form action="{{ route('logout') }}" method="POST" class="float-right">
                            @csrf
                            <button type="submit" class="btn btn-default btn-flat text-danger">
                                Cerrar Sesión
                            </button>
                        </form>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>

    {{-- Sidebar --}}
    <aside class="main-sidebar sidebar-dark-primary elevation-2">
        <a href="{{ $homeRoute }}" class="brand-link">
            <img src="{{ asset('images/Rene_Barrientos_Ortuno.jpg') }}"
                 alt="Escudo"
                 class="brand-image img-circle elevation-2">
            <span class="brand-text font-weight-bold text-white">
                SICCAM - QR
                <span class="brand-subtitle">Control de Asistencia</span>
            </span>
        </a>

        <div class="sidebar">
            <nav class="mt-3">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                    {{-- ========== MENÚ ADMIN ========== --}}
                    @if($esAdmin)
                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-speedometer2"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('estudiantes.index') }}" class="nav-link {{ request()->routeIs('estudiantes.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-people"></i>
                                <p>Estudiantes</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('docentes.index') }}" class="nav-link {{ request()->routeIs('docentes.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-person-badge"></i>
                                <p>Docentes</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('examenes.index') }}" class="nav-link {{ request()->routeIs('examenes.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-journal-check"></i>
                                <p>Exámenes</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('asistencias.index') }}" class="nav-link {{ request()->routeIs('asistencias.index') || request()->routeIs('asistencias.show') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-calendar-check"></i>
                                <p>Asistencias</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('asistencias.manual') }}" class="nav-link {{ request()->routeIs('asistencias.manual*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-pencil-square"></i>
                                <p>Asistencia Manual</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('reportes.index') }}" class="nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-bar-chart"></i>
                                <p>Reportes</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('comunicados.index') }}" class="nav-link {{ request()->routeIs('comunicados.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-megaphone"></i>
                                <p>Comunicados</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('configuracion.index') }}" class="nav-link {{ request()->routeIs('configuracion.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-gear"></i>
                                <p>Configuración</p>
                            </a>
                        </li>
                    @endif

                    {{-- ========== MENÚ DOCENTE ========== --}}
                    @if($esDocente)
                        <li class="nav-item">
                            <a href="{{ route('docente.dashboard') }}" class="nav-link {{ request()->routeIs('docente.dashboard') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-speedometer2"></i>
                                <p>Mi Panel</p>
                            </a>
                        </li>

                        {{-- Cuando existan estas rutas, se activan solas --}}
                        {{-- 
                        <li class="nav-item">
                            <a href="{{ route('docente.asistencias.manual') }}" class="nav-link {{ request()->routeIs('docente.asistencias*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-pencil-square"></i>
                                <p>Asistencia Manual</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('docente.examenes.index') }}" class="nav-link {{ request()->routeIs('docente.examenes*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-journal-check"></i>
                                <p>Exámenes</p>
                            </a>
                        </li>
                        --}}
                    @endif

                    <li class="nav-header">CUENTA</li>
                    <li class="nav-item">
                        <form action="{{ route('logout') }}" method="POST" class="d-inline w-100">
                            @csrf
                            <button type="submit" class="nav-link text-warning border-0 bg-transparent w-100 text-left" style="cursor:pointer;">
                                <i class="nav-icon bi bi-box-arrow-right"></i>
                                <p>Cerrar Sesión</p>
                            </button>
                        </form>
                    </li>

                </ul>
            </nav>
        </div>
    </aside>

    {{-- Content --}}
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 font-weight-bold" style="color: #1a5f2a;">
                            @yield('page_title', 'Dashboard')
                        </h1>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                @yield('content')
            </div>
        </section>
    </div>

    <footer class="main-footer text-sm text-center">
        <strong>SICCAM - QR &copy; 2026.</strong> Sistema de Control de Asistencia.
    </footer>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script src="{{ asset('js/estudiantes.js') }}"></script>
<script src="{{ asset('js/asistencias-realtime.js') }}"></script>

@stack('scripts')
</body>
</html>