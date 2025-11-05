<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura F-{{ str_pad($factura->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; line-height: 1.5; margin: 20px; }
        .factura-container { border: 2px solid #000; padding: 20px; max-width: 800px; margin: auto; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 10px; }
        .header .logo { max-width: 100px; /* Ajusta tu logo */ }
        .header .empresa-info { text-align: left; }
        .header .empresa-info h1 { font-size: 20px; margin: 0; }
        .header .empresa-info p { margin: 0; font-size: 11px; }
        .header .factura-info { text-align: right; border: 1px solid #000; padding: 5px; }
        .header .factura-info h2 { font-size: 16px; margin: 0 0 5px 0; }
        .afiliado-info { border-bottom: 1px solid #ccc; padding-bottom: 10px; margin-bottom: 10px; }
        .afiliado-info table { width: 100%; }
        .afiliado-info td { padding: 2px 5px; font-size: 11px; }
        .detalle-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .detalle-table th, .detalle-table td { border: 1px solid #000; padding: 6px; text-align: left; }
        .detalle-table thead th { background-color: #f2f2f2; text-align: center; }
        .detalle-table .descripcion { width: 60%; }
        .detalle-table .monto { text-align: right; }
        .totales { float: right; width: 40%; margin-top: 15px; }
        .totales table { width: 100%; border-collapse: collapse; }
        .totales td { padding: 5px; }
        .totales tr td:first-child { text-align: right; font-weight: bold; }
        .totales tr.total-final td { font-size: 14px; font-weight: bold; border-top: 2px solid #000; }
        .footer { margin-top: 40px; font-size: 10px; text-align: center; color: #777; clear: both; }
        .estado-factura { text-align: center; font-size: 24px; font-weight: bold; padding: 10px; border: 2px dashed; }
        .estado-pagado { color: green; border-color: green; }
        .estado-impaga { color: red; border-color: red; }
        .estado-anulada { color: #888; border-color: #888; }
        .print-button { display: block; width: 150px; margin: 20px auto; padding: 10px; background-color: #007bff; color: white; border: none; rounded; cursor: pointer; text-align: center; font-size: 14px; }
        @media print {
            .print-button { display: none; }
            body { margin: 0; }
            .factura-container { border: none; max-width: 100%; box-shadow: none; margin: 0; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="factura-container">
        <div class="header">
            <div class="empresa-info">
                <h1>COOPERATIVA DE AGUA [TU NOMBRE]</h1>
                <p>[Tu Dirección] - [Tu Teléfono] - [Tu Ciudad]</p>
                <p>[Tu NIT o Información Legal]</p>
            </div>
            <div class="factura-info">
                <h2>FACTURA OFICIAL</h2>
                <p><strong>N°:</strong> F-{{ str_pad($factura->id, 6, '0', STR_PAD_LEFT) }}</p>
                <p><strong>Fecha Emisión:</strong> {{ \Carbon\Carbon::parse($factura->fecha_emision)->format('d/m/Y') }}</p>
                <p><strong>Período:</strong> {{ $factura->periodo }}</p>
            </div>
        </div>

        <div class="afiliado-info">
            <h3>Datos del Afiliado</h3>
            <table>
                <tr>
                    <td>Afiliado:</td>
                    <td>{{ $factura->conexion->afiliado->nombre_completo ?? 'N/A' }}</td>
                    <td>CI:</td>
                    <td>{{ $factura->conexion->afiliado->ci ?? 'N/A' }}</td>
                </tr>
                 <tr>
                    <td>Medidor:</td>
                    <td>{{ $factura->conexion->codigo_medidor }}</td>
                    <td>Dirección:</td>
                    <td>{{ $factura->conexion->direccion }}</td>
                </tr>
            </table>
        </div>

        <div class="detalle-cobro">
            <table class="detalle-table">
                <thead>
                    <tr>
                        <th class="descripcion">Concepto</th>
                        <th>Lect. Ant.</th>
                        <th>Lect. Act.</th>
                        <th>Consumo (m³)</th>
                        <th class="monto">Subtotal (Bs)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Consumo de Agua Período {{ $factura->periodo }}</td>
                        <td style="text-align: center;">{{ number_format($factura->lectura?->lectura_anterior, 2) }}</td>
                        <td style="text-align: center;">{{ number_format($factura->lectura?->lectura_actual, 2) }}</td>
                        <td style="text-align: center;">{{ number_format($consumo, 2) }}</td>
                        <td class="monto">{{ number_format($factura->monto_total, 2) }}</td>
                    </tr>
                    </tbody>
            </table>
        </div>

        <div class="totales">
            <table>
                <tr class="total-final">
                    <td>TOTAL A PAGAR:</td>
                    <td>Bs {{ number_format($factura->monto_total, 2) }}</td>
                </tr>
            </table>
        </div>

        <div style="clear: both; margin-top: 80px;"></div>

        @if($factura->estado == 'pagado')
            <div class="estado-factura estado-pagado">
                PAGADO
            </div>
            <p style="text-align: center; margin-top: 5px;">
                Fecha de Pago: {{ $factura->fecha_pago ? \Carbon\Carbon::parse($factura->fecha_pago)->format('d/m/Y') : 'N/A' }} <br>
                @if($ultimoPago)
                Forma de Pago: {{ $ultimoPago->forma_pago }} 
                @if($ultimoPago->referencia) (Ref: {{ $ultimoPago->referencia }}) @endif
                @endif
            </p>
        @elseif($factura->estado == 'impaga')
             <div class="estado-factura estado-impaga">
                IMPAGA
            </div>
             <p style="text-align: center; margin-top: 5px;">
                Válido para pagar hasta: {{ $factura->fecha_vencimiento ? \Carbon\Carbon::parse($factura->fecha_vencimiento)->format('d/m/Y') : 'N/A' }}
            </p>
        @elseif($factura->estado == 'anulada')
             <div class="estado-factura estado-anulada">
                FACTURA ANULADA
            </div>
        @endif


        <div class="footer">
            Esta es una factura oficial. Gracias por su pago.
            <br>
            Impreso el: {{ $fechaImpresion }}
        </div>
    </div>

    <button class="print-button" onclick="window.print()">🖨️ Imprimir Factura</button>
</body>
</html>