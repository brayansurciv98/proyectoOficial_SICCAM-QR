<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Credenciales Tutor</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #222; }
        .header { text-align: center; margin-bottom: 25px; }
        .header h2 { color: #1a5f2a; margin: 0; }
        .header p { margin: 4px 0; color: #555; }
        .box {
            border: 2px solid #1a5f2a;
            border-radius: 8px;
            padding: 18px;
            margin-top: 10px;
        }
        .row { margin-bottom: 10px; }
        .label { font-weight: bold; color: #1a5f2a; }
        .pass {
            background: #e8f5e9;
            padding: 6px 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 15px;
        }
        .footer {
            margin-top: 30px;
            font-size: 11px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>SICCAM-QR</h2>
        <p>{{ \App\Models\Configuracion::colegio(true) }}</p>
        <p><strong>Credenciales de Acceso - Tutor / Padre de Familia</strong></p>
    </div>

    <div class="box">
        <div class="row">
            <span class="label">Tutor:</span>
            {{ $tutor_nombre }}
        </div>
        <div class="row">
            <span class="label">Estudiante:</span>
            {{ $estudiante }} ({{ $codigo_est }})
        </div>
        <div class="row">
            <span class="label">Usuario (CI/Email):</span>
            {{ $usuario }}
        </div>
        <div class="row">
            <span class="label">Contraseña inicial:</span>
            <span class="pass">{{ $password }}</span>
        </div>
        <div class="row">
            <span class="label">Generado:</span>
            {{ $fecha }}
        </div>
    </div>

    <div class="footer">
        Guarde este documento. El tutor usará estas credenciales para ingresar a la App.<br>
        No se modificó la contraseña; solo se recuperaron los datos de acceso.
    </div>
</body>
</html>