<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SICCAM QR - Control de Asistencia')</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    {{-- Librerías locales (public/vendor): el panel funciona sin internet --}}
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/toastify/toastify.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">

    <style>
        .modal-backdrop {
            z-index: 1040 !important;
        }
        .modal {
            z-index: 1050 !important;
        }
    </style>

    @vite(['resources/js/app.js'])
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
@php
    $esAdmin = Auth::guard('web')->check();
    $esDocente = Auth::guard('docente')->check();
    $usuario = $esAdmin ? Auth::guard('web')->user() : Auth::guard('docente')->user();

    $usuarioNombre = $esAdmin
        ? ($usuario?->name ?? 'Administrador')
        : ($usuario?->nombres ?? 'Docente');
    $usuarioCompleto = $esAdmin
        ? ($usuario?->name ?? 'Administrador')
        : trim(($usuario?->nombres ?? '') . ' ' . ($usuario?->apellidos ?? ''));
    $usuarioRol = $esAdmin ? 'Administrador' : 'Docente';
    $usuarioDetalle = $esAdmin
        ? ($usuario?->email ?? '')
        : 'Materia: ' . ($usuario?->materia?->nombre ?? '—');
    // "Julio Gutierrez" -> "JG"
    $usuarioIniciales = mb_strtoupper(
        collect(preg_split('/\s+/', $usuarioCompleto))->filter()->take(2)
            ->map(fn ($parte) => mb_substr($parte, 0, 1))->implode(''),
        'UTF-8'
    );
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
                    <h4 class="mb-0">{{ \App\Models\Configuracion::colegio(true) }}</h4>
                </span>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown menu-usuario">
                <a href="#" class="nav-link d-flex align-items-center" data-toggle="dropdown" aria-expanded="false">
                    <span class="avatar-usuario mr-2">{{ $usuarioIniciales }}</span>
                    <span class="d-none d-md-inline text-left">
                        <span class="d-block font-weight-bold text-dark" style="line-height: 1.1;">{{ $usuarioNombre }}</span>
                        <small class="text-muted">{{ $usuarioRol }}</small>
                    </span>
                    <i class="bi bi-chevron-down text-muted ml-2 small"></i>
                </a>

                <div class="dropdown-menu dropdown-menu-right p-0 menu-usuario-panel">
                    <div class="menu-usuario-cabecera text-center">
                        <span class="avatar-usuario avatar-grande mb-2">{{ $usuarioIniciales }}</span>
                        <div class="font-weight-bold">{{ $usuarioCompleto }}</div>
                        <span class="badge badge-light text-success mt-1">{{ $usuarioRol }}</span>
                        @if($usuarioDetalle)
                            <div class="small mt-2" style="opacity: .85;">{{ $usuarioDetalle }}</div>
                        @endif
                    </div>

                   

                    <button type="button" class="dropdown-item py-2 text-danger" data-toggle="modal" data-target="#modalCerrarSesion">
                        <i class="bi bi-box-arrow-right mr-2"></i> Cerrar sesión
                    </button>
                </div>
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
                            <a href="{{ route('horarios.index') }}" class="nav-link {{ request()->routeIs('horarios.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-calendar-week"></i>
                                <p>Horarios</p>
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
                        <li class="nav-item">
                            <a href="{{ route('docente.clases.index') }}" class="nav-link {{ request()->routeIs('docente.clases.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-calendar-check"></i>
                                <p>Mis clases de hoy</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('docente.asistencias.manual') }}" class="nav-link {{ request()->routeIs('docente.asistencias.manual*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-clipboard-check"></i>
                                <p>Asistencia Manual</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('docente.examenes.index') }}"
                               class="nav-link {{ request()->routeIs('docente.examenes.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-journal-check"></i>
                                <p>Mis Exámenes</p>
                            </a>
                        </li>
                    @endif

                    

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

{{-- Confirmación al cerrar sesión (la usan el menú de arriba y el lateral) --}}
<form id="form-logout" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>

<div class="modal fade" id="modalCerrarSesion" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
        <div class="modal-content border-0" style="border-radius: 12px;">
            <div class="modal-body text-center p-4">
                <div class="mb-3">
                    <i class="bi bi-box-arrow-right text-danger" style="font-size: 2.6rem;"></i>
                </div>
                <h5 class="font-weight-bold mb-2">Cerrar sesión</h5>
                <p class="text-muted mb-4">¿Desea cerrar sesión, {{ $usuarioNombre }}?</p>
                <div class="d-flex justify-content-center">
                    <button type="button" class="btn btn-outline-secondary mr-2 px-4" data-dismiss="modal">No</button>
                    <button type="submit" form="form-logout" class="btn btn-danger px-4">Sí, salir</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/adminlte/adminlte.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('vendor/toastify/toastify.min.js') }}"></script>

<script>
    $(document).ready(function() {
        $(document).on('show.bs.modal', '.modal', function () {
            $(this).appendTo('body');
        });
    });
</script>

<script src="{{ asset('js/estudiantes.js') }}"></script>
<script src="{{ asset('js/asistencias-realtime.js') }}"></script>

@yield('js')
@stack('scripts')
</body>
</html>