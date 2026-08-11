<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar Sesión - SICCAM QR</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap Icons (opcional si usas BI) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Theme style AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <style>
        .login-page {
            background: linear-gradient(135deg, #1a5f2a, #0d3b16) !important;
        }
        .card-outline.card-success {
            border-top: 3px solid #2e7d32;
        }
        .btn-success {
            background-color: #2e7d32;
            border-color: #2e7d32;
        }
        .btn-success:hover {
            background-color: #1a5f2a;
            border-color: #1a5f2a;
        }
        .custom-control-input:checked ~ .custom-control-label::before {
            background-color: #2e7d32;
            border-color: #2e7d32;
        }
    </style>
</head>
<body class="hold-transition login-page">
<div class="login-box">
    
    <!-- Logo/Marca Superior -->
    <div class="login-logo text-center mb-3">
        <a href="#" class="text-white font-weight-bold">
            <b>SICCAM</b> - QR
        </a>
    </div>

    <!-- Card Principal de Login -->
    <div class="card card-outline card-success shadow-lg">
        <div class="card-header text-center py-3">
            <h4 class="m-0 font-weight-bold text-dark">Iniciar Sesión</h4>
            <small class="text-muted">Control de Asistencia Escolar</small>
        </div>
        
        <div class="card-body login-card-body">
            <p class="login-box-msg p-0 mb-3 text-muted small">Ingresa tus credenciales para acceder</p>

            <form action="{{ route('dashboard') }}" method="POST">
                @csrf

                <!-- Campo Email -->
                <div class="input-group mb-3">
                    <input type="email" class="form-control" name="email" placeholder="Correo electrónico" required autofocus>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-envelope text-muted"></span>
                        </div>
                    </div>
                </div>

                <!-- Campo Contraseña -->
                <div class="input-group mb-3">
                    <input type="password" class="form-control" name="password" placeholder="Contraseña" required>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock text-muted"></span>
                        </div>
                    </div>
                </div>

                <!-- Opciones (Remember + Submit) -->
                <div class="row align-items-center mb-3">
                    <div class="col-7">
                        <div class="icheck-primary custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="remember" name="remember">
                            <label class="custom-control-label small text-muted" for="remember">Recordarme</label>
                        </div>
                    </div>
                    <div class="col-5">
                        <button type="submit" class="btn btn-success btn-block font-weight-bold">
                            Ingresar
                        </button>
                    </div>
                </div>
            </form>

        </div>
        
        <div class="card-footer text-center bg-light py-2">
            <small class="text-muted font-weight-bold">
                Unidad Educativa René Barrientos Ortuño "A"
            </small>
        </div>
    </div>
</div>

<!-- Scripts de Bootstrap 4 y AdminLTE -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>