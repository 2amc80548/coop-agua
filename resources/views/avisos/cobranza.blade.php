<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aviso de Cobranza - {{ $lectura->periodo }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 11px; line-height: 1.4; margin: 0; padding: 15px; }
        .aviso-container { border: 1px solid #000; padding: 10px; max-width: 400px; margin: auto; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 10px; }
        .header img { max-width: 80px; height: auto; /* Ajusta el tamaño de tu logo */ }
        .header h1 { font-size: 16px; margin: 5px 0 0 0; }
        .header p { margin: 0; font-size: 10px; }
        .content { margin-bottom: 10px; }
        .content h2 { text-align: center; font-size: 14px; margin: 10px 0; background-color: #eee; padding: 5px; }
        .info-table, .lectura-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .info-table td { padding: 3px; }
        .info-table tr td:first-child { font-weight: bold; width: 30%; }
        .lectura-table th, .lectura-table td { border: 1px solid #000; padding: 5px; text-align: center; }
        .lectura-table th { background-color: #f2f2f2; font-size: 10px; }
        .total-section { margin-top: 15px; }
        .total-section p { margin: 5px 0; font-size: 12px; display: flex; justify-content: space-between; }
        .total-section .total { font-size: 14px; font-weight: bold; border-top: 1px dashed #000; padding-top: 5px; }
        .footer { margin-top: 15px; font-size: 9px; text-align: center; color: #555; border-top: 1px solid #eee; padding-top: 10px; }
        .print-button { display: block; width: 150px; margin: 20px auto; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; text-align: center; font-size: 14px; }
        
        @media print {
            body { margin: 0; padding: 0; }
            .print-button { display: none; }
            .aviso-container { border: none; max-width: 100%; box-shadow: none; margin: 0; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="aviso-container">
        <div class="header">
            <h1>COOPERATIVA DE AGUA [TU NOMBRE]</h1>
            <p>[Tu Dirección] - [Tu Teléfono] - [Tu Ciudad]</p>
            <h2>AVISO DE LECTURA Y COBRANZA</h2>
        </div>

        <div class="content">
            <table class="info-table">
                <tr>
                    <td>Afiliado:</td>
                    <td>{{ $lectura->conexion->afiliado->nombre_completo ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Dirección:</td>
                    <td>{{ $lectura->conexion->direccion }}</td>
                </tr>
                <tr>
                    <td>Medidor:</td>
                    <td>{{ $lectura->conexion->codigo_medidor }}</td>
                </tr>
                <tr>
                    <td>Período:</td>
                    <td><strong>{{ $lectura->periodo }}</strong></td>
                </tr>
            </table>

            <table class="lectura-table">
                <thead>
                    <tr>
                        <th>Fecha Lectura</th>
                        <th>Lect. Anterior</th>
                        <th>Lect. Actual</th>
                        <th>Consumo (m³)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($lectura->fecha_lectura)->format('d/m/Y') }}</td>
                        <td>{{ number_format($lectura->lectura_anterior, 2) }}</td>
                        <td>{{ number_format($lectura->lectura_actual, 2) }}</td>
                        <td><strong>{{ number_format($consumo, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>

            <div class="total-section">
                @if($descuentoAplicado > 0)
                    <p>
                        <span>Monto Cálculo:</span>
                        <span>Bs {{ number_format($montoEstimado + $descuentoAplicado, 2) }}</span>
                    </p>
                    <p style="color: green;">
                        <span>Descuento Adulto Mayor ({{ $tarifa->descuento_adulto_mayor_pct }}%):</span>
                        <span>- Bs {{ number_format($descuentoAplicado, 2) }}</span>
                    </p>
                @endif
                <p class="total">
                    <span>Monto Estimado a Pagar:</span>
                    <span>Bs {{ number_format($montoEstimado, 2) }}</span>
                </p>
            </div>
            
            @if($lectura->observacion)
                <div style="margin-top: 10px; font-size: 10px;">
                    <strong>Observación:</strong> {{ $lectura->observacion }}
                </div>
            @endif

        </div>

        <div class="footer">
            Este es un aviso preventivo y no una factura oficial. El monto es un estimado y puede estar sujeto a otros cargos fijos o ajustes en la factura final.
            <br>
            Registrado por: {{ $lectura->usuarioRegistrado?->name ?? 'Sistema' }} | Impreso el: {{ $fechaImpresion }}
        </div>
    </div>

    <button class="print-button" onclick="window.print()">🖨️ Imprimir Aviso</button>

</body>
</html>