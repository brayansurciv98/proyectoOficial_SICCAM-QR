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
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Theme style AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <style>
        :root {
            --verde-principal: #1a5f2a;
            --verde-claro: #2e7d32;
            --verde-oscuro: #0d3b16;
        }

        .login-page {
            background: linear-gradient(
                135deg,
                #ffffff 0%,
                #ffffff 48%,
                var(--verde-principal) 48%,
                var(--verde-claro) 100%
            ) !important;
            min-height: 100vh;
        }

        .login-box {
            width: 400px;
        }

        .brand-area {
            text-align: center;
            margin-bottom: 1.25rem;
        }

        .brand-area img {
            width: 115px;
            height: 115px;
            object-fit: contain;
            margin-bottom: 0.7rem;
            filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.25));
        }

        .brand-area .brand-title {
            display: block;
            font-size: 1.85rem;
            font-weight: 700;
            color: var(--verde-principal);
            text-decoration: none;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.75);
            line-height: 1.2;
        }

        .brand-area .brand-subtitle {
            margin-top: 0.25rem;
            color: #333;
            font-size: 0.95rem;
        }

        .card-outline.card-success {
            border-top: 3px solid var(--verde-claro);
            border-radius: 12px;
            overflow: hidden;
        }

        .btn-success {
            background-color: var(--verde-principal);
            border-color: var(--verde-principal);
        }

        .btn-success:hover {
            background-color: var(--verde-claro);
            border-color: var(--verde-claro);
        }

        .custom-control-input:checked ~ .custom-control-label::before {
            background-color: var(--verde-principal);
            border-color: var(--verde-principal);
        }

        .card-footer {
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body class="hold-transition login-page">
<div class="login-box">

    <!-- Escudo + Marca -->
    <div class="brand-area">
        <img src="{{ asset('images/Rene_Barrientos_Ortuno.jpg') }}" alt="Escudo U.E. René Barrientos Ortuño">
        <a href="#" class="brand-title">
            <b>SICCAM</b> - QR
        </a>
        <div class="brand-subtitle">Control de Asistencia Escolar</div>
    </div>

    <!-- Card Principal de Login -->
    <div class="card card-outline card-success shadow-lg">
        <div class="card-header text-center py-3">
            <h4 class="m-0 font-weight-bold text-dark">Iniciar Sesión</h4>
            <small class="text-muted">Acceso al sistema administrativo</small>
        </div>

        <div class="card-body login-card-body">
            <p class="login-box-msg p-0 mb-3 text-muted small">Ingresa tus credenciales para acceder</p>

            @if ($errors->any())
                <div class="alert alert-danger py-2 px-3 small mb-3">
                    <ul class="m-0 pl-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST">
                @csrf

                <!-- Campo Email -->
                <div class="input-group mb-3">
                    <input type="text"
                        class="form-control @error('email') is-invalid @enderror"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Correo o CI"
                        required autofocus>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-envelope text-muted"></span>
                        </div>
                    </div>
                </div>

                <!-- Campo Contraseña -->
                <div class="input-group mb-3">
                    <input type="password"
                           class="form-control"
                           name="password"
                           placeholder="Contraseña"
                           required>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock text-muted"></span>
                        </div>
                    </div>
                </div>

                <!-- Opciones -->
                <div class="row align-items-center mb-2">
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>