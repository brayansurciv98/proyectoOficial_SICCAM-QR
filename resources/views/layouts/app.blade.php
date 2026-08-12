<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SICCAM QR - Control de Asistencia')</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Theme style AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- DataTables Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">

    <style>
        :root {
            --verde-principal: #1a5f2a;
            --verde-oscuro: #0d3b16;
            --verde-claro: #2e7d32;
        }

        /* Personalización de Sidebar Institucional */
        .main-sidebar {
            background: linear-gradient(180deg, var(--verde-principal), var(--verde-oscuro)) !important;
        }
        
        .brand-link {
            border-bottom: 1px solid rgba(255, 255, 255, 0.15) !important;
        }

        .sidebar-dark-primary .nav-sidebar>.nav-item>.nav-link.active,
        .sidebar-light-primary .nav-sidebar>.nav-item>.nav-link.active {
            background-color: rgba(255, 255, 255, 0.2) !important;
            color: #fff !important;
            border-left: 4px solid #fff;
        }

        .btn-verde {
            background-color: var(--verde-claro) !important;
            color: #fff !important;
            border: none;
        }

        .btn-verde:hover {
            background-color: var(--verde-oscuro) !important;
            color: #fff !important;
        }
    </style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar Top -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom-0 shadow-sm">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <span class="nav-link fw-bold text-success" style="color: var(--verde-principal) !important;"><h4>U.E. René Barrientos Ortuño "A"</h4></span>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                    <span class="d-none d-md-inline font-weight-bold text-dark me-2">Administrador</span>
                    <i class="bi bi-person-circle text-success fs-5"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <li class="user-header bg-success">
                        <p>
                            Usuario Administrador
                            <small>SICCAM - QR</small>
                        </p>
                    </li>
                    <li class="user-footer">
                        <a href="{{ route('login') }}" class="btn btn-default btn-flat float-right text-danger">Cerrar Sesión</a>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-2">
        <!-- Brand Logo -->
        <a href="{{ route('dashboard') }}" class="brand-link text-center">
            <span class="brand-text font-weight-bold text-white">SICCAM - QR</span>
            <br>
            <small class="text-white-50 fs-7">Control de Asistencia</small>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <nav class="mt-3">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    
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
                        <a href="{{ route('asistencias.index') }}" class="nav-link {{ request()->routeIs('asistencias.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-calendar-check"></i>
                            <p>Asistencias</p>
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

                    <li class="nav-header">CUENTA</li>
                    <li class="nav-item">
                        <a href="{{ route('login') }}" class="nav-link text-warning">
                            <i class="nav-icon bi bi-box-arrow-right"></i>
                            <p>Cerrar Sesión</p>
                        </a>
                    </li>

                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper bg-light">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 font-weight-bold" style="color: var(--verde-principal);">@yield('page_title', 'Dashboard')</h1>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                @yield('content')
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="main-footer text-sm text-center">
        <strong>SICCAM - QR &copy; 2026.</strong> Sistema de Control de Asistencia.
    </footer>
</div>

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<!-- Bootstrap 4 / AdminLTE JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<!-- DataTables JS y Bootstrap 4 Integration -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>

<!-- Archivo JS para el módulo de Estudiantes -->
<script src="{{ asset('js/estudiantes.js') }}"></script>

@stack('scripts')
</body>
</html>