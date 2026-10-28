<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Credenciales QR</title>
    <style>
        @page { margin: 1.2cm 1.6cm; }
        body { font-family: DejaVu Sans, sans-serif; color: #17251c; margin: 0; }
        table.hoja { border-collapse: separate; border-spacing: 0.35cm 0.3cm; margin: 0 auto; }
        table.hoja tr { page-break-inside: avoid; }
        td.celda { width: 8.6cm; height: 5.4cm; padding: 0; vertical-align: top; }
        /* Borde punteado = línea de corte (tamaño carnet 8,6 x 5,4 cm) */
        .tarjeta { width: 8.6cm; height: 5.4cm; border: 1px dashed #8a968f; border-radius: 6px; overflow: hidden; }
        .banda { background: #166534; color: #fff; height: 1.15cm; }
        .banda table { width: 100%; border-collapse: collapse; }
        .banda td { vertical-align: middle; padding: 0.12cm 0.2cm; }
        .banda img { width: 0.85cm; height: 0.85cm; }
        .sistema { font-size: 8pt; font-weight: bold; line-height: 1.2; }
        .colegio { font-size: 6.5pt; letter-spacing: 0.3px; margin-top: 1px; }
        .cuerpo { width: 100%; border-collapse: collapse; }
        .cuerpo td { vertical-align: middle; }
        .qr { width: 3.3cm; padding: 0.1cm 0 0 0.15cm; }
        .qr img { width: 3.2cm; height: 3.2cm; }
        .datos { padding: 0.1cm 0.25cm 0 0.1cm; }
        .nombres { font-size: 10pt; font-weight: bold; line-height: 1.15; }
        .apellidos { font-size: 9pt; font-weight: bold; color: #14532d; line-height: 1.15; margin-bottom: 0.18cm; }
        .dato { font-size: 7.5pt; color: #4b5a52; margin-top: 0.05cm; }
        .dato b { color: #17251c; }
        .pie { font-size: 6pt; color: #6b7a72; text-align: center; padding-top: 0.05cm; }
    </style>
</head>
<body>
    <table class="hoja">
        @foreach($filas as $fila)
            <tr>
                @foreach($fila as $t)
                    <td class="celda">
                        <div class="tarjeta">
                            <div class="banda">
                                <table>
                                    <tr>
                                        @if($logo)
                                            <td style="width: 0.95cm;"><img src="{{ $logo }}" alt=""></td>
                                        @endif
                                        <td>
                                            <div class="sistema">{{ $colegio }}</div>
                                            <div class="colegio">Credencial estudiantil · SICCAM-QR</div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <table class="cuerpo">
                                <tr>
                                    <td class="qr"><img src="{{ $t['qr'] }}" alt="QR"></td>
                                    <td class="datos">
                                        <div class="nombres">{{ $t['nombres'] }}</div>
                                        <div class="apellidos">{{ $t['apellidos'] }}</div>
                                        <div class="dato">Código: <b>{{ $t['codigo'] }}</b></div>
                                        <div class="dato">Curso: <b>{{ $t['curso'] }} Secundaria</b></div>
                                        <div class="dato">Gestión: <b>{{ $gestion }}</b></div>
                                    </td>
                                </tr>
                            </table>
                            <div class="pie">Muestre este código QR a la cámara al ingresar a la unidad educativa</div>
                        </div>
                    </td>
                @endforeach
                @if(count($fila) === 1)
                    <td class="celda"></td>
                @endif
            </tr>
        @endforeach
    </table>
</body>
</html>
