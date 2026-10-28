<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Resultados SICCAM-QR</title>
    <style>
        @page { margin: 1.4cm 2cm 1.2cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #1f2a24; }
        .encabezado { border-bottom: 2px solid #166534; padding-bottom: 8px; margin-bottom: 14px; }
        .encabezado h1 { font-size: 15pt; color: #14532d; margin: 0; }
        .encabezado p { margin: 3px 0 0; color: #4b5a52; font-size: 9pt; }
        h2 { font-size: 10pt; color: #14532d; margin: 8px 0 3px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 8px; border-bottom: 1px solid #dde7e0; line-height: 1.2; }
        td.n { text-align: right; font-weight: bold; width: 32%; }
        td.sub { padding-left: 22px; color: #3c4a42; }
        tr:nth-child(even) td { background: #f5faf6; }
        .resumen td { border: 1px solid #cfe3d6; text-align: center; width: 25%; background: #f2faf4; }
        .resumen .valor { font-size: 13pt; font-weight: bold; color: #14532d; }
        .resumen .texto { font-size: 8pt; color: #4b5a52; }
        .nota { font-size: 8pt; color: #6b7a72; margin-top: 14px; }
        .firmas { margin-top: 8px; }
        .firmas td { border: none; text-align: center; width: 50%; padding-top: 34px; font-size: 8.5pt; }
        .linea { border-top: 1px solid #555; margin: 0 25px; padding-top: 4px; }
    </style>
</head>
<body>
@php
    $periodo = \Carbon\Carbon::parse($desde)->format('d/m/Y') . ($desde !== $hasta ? ' al ' . \Carbon\Carbon::parse($hasta)->format('d/m/Y') : '');
    $pct = fn ($parte, $total) => $total ? ' (' . round($parte / $total * 100, 1) . ' %)' : '';
@endphp

<div class="encabezado">
    <h1>Informe de resultados del sistema SICCAM-QR</h1>
    <p>{{ $colegio }} · Periodo: {{ $periodo }} · Generado el {{ $generado }}</p>
</div>

<table class="resumen">
    <tr>
        <td><div class="valor">{{ $lecturasQr }}</div><div class="texto">lecturas del lector QR</div></td>
        <td><div class="valor">{{ $segundosPromedio !== null ? $segundosPromedio . ' s' : '—' }}</div><div class="texto">tiempo promedio de registro</div></td>
        <td><div class="valor">{{ $avisosPush }}/{{ $avisos }}</div><div class="texto">avisos enviados por push</div></td>
        <td><div class="valor">{{ $coberturaDiaria !== null ? $coberturaDiaria . ' %' : '—' }}</div><div class="texto">estudiantes con ingreso por día</div></td>
    </tr>
</table>

<h2>1. Control de ingreso</h2>
<table>
    <tr><td>Días con registros</td><td class="n">{{ $dias }}</td></tr>
    <tr><td>Estudiantes activos</td><td class="n">{{ $activos }}</td></tr>
    <tr><td>Registros de ingreso</td><td class="n">{{ $totalRegistros }}</td></tr>
    <tr><td class="sub">Presentes</td><td class="n">{{ $presentes }}{{ $pct($presentes, $totalRegistros) }}</td></tr>
    <tr><td class="sub">Tardanzas</td><td class="n">{{ $tardanzas }}{{ $pct($tardanzas, $totalRegistros) }}</td></tr>
    <tr><td class="sub">Ausentes</td><td class="n">{{ $ausentes }}{{ $pct($ausentes, $totalRegistros) }}</td></tr>
</table>

<h2>2. Origen de los registros</h2>
<table>
    @foreach($origen as $nombre => $cantidad)
        @continue($nombre === 'Otros' && !$cantidad)
        <tr><td>{{ $nombre }}</td><td class="n">{{ $cantidad }}{{ $pct($cantidad, $totalRegistros) }}</td></tr>
    @endforeach
</table>

<h2>3. Lector QR (Raspberry Pi)</h2>
<table>
    <tr><td>Lecturas registradas</td><td class="n">{{ $lecturasQr }}</td></tr>
    <tr><td class="sub">Registradas al instante (con conexión)</td><td class="n">{{ $lecturasEnLinea }}</td></tr>
    <tr><td class="sub">Guardadas sin conexión y enviadas después</td><td class="n">{{ $lecturasSincronizadas }}</td></tr>
    <tr><td>Tiempo promedio desde la lectura hasta el registro</td><td class="n">{{ $segundosPromedio !== null ? $segundosPromedio . ' s' : '—' }}</td></tr>
    <tr><td>Tiempo máximo (con conexión)</td><td class="n">{{ $segundosMaximo !== null ? $segundosMaximo . ' s' : '—' }}</td></tr>
</table>

<h2>4. Avisos a padres de familia y uso del sistema</h2>
<table>
    <tr><td>Avisos generados (ausencia / retraso / comunicado / examen)</td><td class="n">{{ implode(' / ', $avisosPorTipo) }}</td></tr>
    <tr><td>Enviados como notificación push</td><td class="n">{{ $avisosPush }} de {{ $avisos }}</td></tr>
    <tr><td>Leídos en la aplicación</td><td class="n">{{ $avisosLeidos }} de {{ $avisos }}</td></tr>
    <tr><td>Tutores con la aplicación instalada</td><td class="n">{{ $tutoresConApp }} de {{ $tutores }}</td></tr>
    <tr><td>Docentes activos</td><td class="n">{{ $docentes }}</td></tr>
    <tr><td>Comunicados enviados</td><td class="n">{{ $comunicados }}</td></tr>
    <tr><td>Exámenes programados</td><td class="n">{{ $examenes }}</td></tr>
    <tr><td>Asistencia de clase tomada por docentes</td><td class="n">{{ $asistenciaClase }} en {{ $clasesConAsistencia }} clase(s)</td></tr>
</table>

<p class="nota">
    Datos obtenidos de la base de datos del sistema. El tiempo de registro se mide desde que la cámara lee el código QR
    (hora de la Raspberry Pi) hasta que el servidor guarda la asistencia; las lecturas que tardaron más de 60 segundos se
    consideran guardadas sin conexión y enviadas después.
</p>

<table class="firmas">
    <tr>
        <td><div class="linea">Responsable del proyecto</div></td>
        <td><div class="linea">Director(a) de la unidad educativa</div></td>
    </tr>
</table>
</body>
</html>
