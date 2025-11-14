<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura F-{{ str_pad($factura->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            margin: 20px;
            color: #111827; /* gris oscuro */
        }

        .factura-container {
            border: 1px solid #111;
            padding: 16px 20px;
            max-width: 800px;
            margin: auto;
        }

        /* HEADER */

        .header {
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .empresa-info {
            flex: 2;
        }

        .empresa-info h1 {
            font-size: 18px;
            margin: 0 0 2px 0;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .empresa-info p {
            margin: 0;
            font-size: 10px;
        }

        .factura-info {
            flex: 1.3;
            border-left: 1px solid #000;
            padding-left: 12px;
            font-size: 11px;
        }

        .factura-info h2 {
            font-size: 14px;
            margin: 0 0 6px 0;
            text-align: right;
            letter-spacing: .08em;
        }

        .factura-info p {
            margin: 0;
            text-align: right;
        }

        .factura-info strong {
            display: inline-block;
            min-width: 98px;
        }

        /* BLOQUE AFILIADO */

        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin: 10px 0 4px 0;
        }

        .afiliado-info {
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .info-table td {
            padding: 2px 4px;
        }

        .info-table td:first-child {
            font-weight: bold;
            width: 16%;
            text-align: right;
            white-space: nowrap;
        }

        /* DETALLE DE COBRO */

        .detalle-cobro {
            margin-top: 8px;
        }

        .detalle-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 11px;
        }

        .detalle-table th,
        .detalle-table td {
            border: 1px solid #000;
            padding: 5px;
        }

        .detalle-table thead th {
            background-color: #f3f4f6;
            text-align: center;
            font-weight: 600;
        }

        .detalle-table .descripcion {
            width: 45%;
        }

        .detalle-table .monto {
            text-align: right;
        }

        /* TOTALES / RESUMEN */

        .resumen-box {
            margin-top: 8px;
            display: flex;
            justify-content: space-between;
            gap: 16px;
        }

        .resumen-left {
            flex: 1.2;
            font-size: 10px;
        }

        .resumen-left p {
            margin: 2px 0;
        }

        .totales {
            flex: 1;
        }

        .totales table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .totales td {
            padding: 4px 5px;
        }

        .totales tr td:first-child {
            text-align: right;
            font-weight: 600;
        }

        .totales tr.total-final td {
            font-size: 13px;
            font-weight: bold;
            border-top: 2px solid #000;
            padding-top: 6px;
        }

        .totales tr.sub-row td {
            border-top: 1px dashed #d1d5db;
        }

        .totales .label-muted {
            font-weight: 500;
            color: #6b7280;
        }

        /* ESTADO FACTURA */

        .estado-factura {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            padding: 8px;
            border: 2px dashed;
            margin-top: 40px;
        }

        .estado-pagado {
            color: #16a34a;
            border-color: #16a34a;
        }

        .estado-impaga {
            color: #dc2626;
            border-color: #dc2626;
        }

        .estado-anulada {
            color: #6b7280;
            border-color: #6b7280;
        }

        .estado-detalle {
            text-align: center;
            margin-top: 6px;
            font-size: 11px;
        }

        /* FOOTER / IMPRESIÓN */

        .footer {
            margin-top: 26px;
            font-size: 9px;
            text-align: center;
            color: #6b7280;
            clear: both;
        }

        .print-button {
            display: block;
            width: 170px;
            margin: 20px auto;
            padding: 8px 10px;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
            font-size: 13px;
        }

        .print-button:hover {
            background-color: #1d4ed8;
        }

        @media print {
            .print-button {
                display: none;
            }
            body {
                margin: 0;
            }
            .factura-container {
                border: none;
                max-width: 100%;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="factura-container">
        {{-- ENCABEZADO --}}
        <div class="header">
            <div class="empresa-info">
                <h1>COOPERATIVA DE AGUA [TU NOMBRE]</h1>
                <p>[Tu Dirección] &middot; [Tu Ciudad]</p>
                <p>Tel: [Tu Teléfono] &middot; NIT: [Tu NIT o Información Legal]</p>
            </div>
            <div class="factura-info">
                <h2>FACTURA OFICIAL</h2>
                <p><strong>N°:</strong> F-{{ str_pad($factura->id, 6, '0', STR_PAD_LEFT) }}</p>
                <p><strong>Fecha emisión:</strong> {{ \Carbon\Carbon::parse($factura->fecha_emision)->format('d/m/Y') }}</p>
                <p><strong>Período:</strong> {{ $factura->periodo }}</p>
            </div>
        </div>

        {{-- DATOS DEL AFILIADO / CONEXIÓN --}}
        <div class="afiliado-info">
            <div class="section-title">Datos del afiliado</div>
            <table class="info-table">
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
                <tr>
                    <td>Tipo conexión:</td>
                    <td>{{ ucfirst($factura->conexion->tipo_conexion ?? 'N/A') }}</td>
                    <td>Estado factura:</td>
                    <td>{{ strtoupper($factura->estado) }}</td>
                </tr>
            </table>
        </div>

        {{-- DETALLE DEL CONSUMO --}}
        <div class="section-title">Detalle de consumo</div>
        <div class="detalle-cobro">
            <table class="detalle-table">
                <thead>
                    <tr>
                        <th class="descripcion">Concepto</th>
                        <th>Lect. ant.</th>
                        <th>Lect. act.</th>
                        <th>Consumo (m³)</th>
                        <th class="monto">Importe (Bs)</th>
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

        {{-- RESUMEN DE CÁLCULO Y TOTALES --}}
        <div class="resumen-box">
            <div class="resumen-left">
                <div class="section-title" style="margin-bottom: 4px;">Resumen del cálculo</div>
                @if(!is_null($montoBase))
                    <p><strong>Base de cálculo:</strong>
                        @if($consumo <= ($tarifa->min_m3 ?? $consumo) ?? false)
                            Mínimo de {{ $tarifa->min_m3 ?? '--' }} m³ / Bs {{ isset($tarifa) ? number_format($tarifa->min_monto, 2) : '—' }}
                        @else
                            Consumo {{ number_format($consumo, 2) }} m³ x Bs {{ isset($tarifa) ? number_format($tarifa->precio_m3, 2) : '—' }} /m³
                        @endif
                    </p>
                @else
                    <p>Cálculo detallado no disponible (tarifa no encontrada o modificada).</p>
                @endif

                @if($factura->conexion->afiliado?->adulto_mayor)
                    <p><strong>Afiliado Adulto Mayor:</strong> Sí</p>
                @else
                    <p><strong>Afiliado Adulto Mayor:</strong> No</p>
                @endif

                <p><strong>Total pagado a la fecha:</strong> Bs {{ number_format($totalPagado, 2) }}</p>
                <p><strong>Saldo pendiente:</strong> Bs {{ number_format($saldo, 2) }}</p>
            </div>

            <div class="totales">
                <table>
                    @if(!is_null($montoBase))
                        <tr class="sub-row">
                            <td class="label-muted">Subtotal por consumo:</td>
                            <td>Bs {{ number_format($montoBase, 2) }}</td>
                        </tr>

                        <tr>
                            <td class="label-muted">
                                Descuento
                                @if($descuentoEstimado > 0 && $porcentajeDescuento > 0)
                                    ({{ $porcentajeDescuento }}% Adulto Mayor)
                                @endif
                                :
                            </td>
                            <td>
                                @if($descuentoEstimado > 0)
                                    - Bs {{ number_format($descuentoEstimado, 2) }}
                                @else
                                    Bs 0.00
                                @endif
                            </td>
                        </tr>
                    @endif

                    <tr class="total-final">
                        <td>TOTAL A PAGAR:</td>
                        <td>Bs {{ number_format($factura->monto_total, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div style="clear: both; margin-top: 40px;"></div>

        {{-- ESTADO VISUAL DE LA FACTURA --}}
        @if($factura->estado == 'pagado')
            <div class="estado-factura estado-pagado">
                PAGADO
            </div>
            <p class="estado-detalle">
                Fecha de pago:
                {{ $factura->fecha_pago ? \Carbon\Carbon::parse($factura->fecha_pago)->format('d/m/Y') : 'N/A' }}<br>
                @if($ultimoPago)
                    Forma de pago: {{ $ultimoPago->forma_pago }}
                    @if($ultimoPago->referencia)
                        (Ref: {{ $ultimoPago->referencia }})
                    @endif
                @endif
            </p>
        @elseif($factura->estado == 'impaga')
            <div class="estado-factura estado-impaga">
                IMPAGA
            </div>
            <p class="estado-detalle">
                Válido para pagar hasta:
                {{ $factura->fecha_vencimiento ? \Carbon\Carbon::parse($factura->fecha_vencimiento)->format('d/m/Y') : 'N/A' }}
            </p>
        @elseif($factura->estado == 'anulada')
            <div class="estado-factura estado-anulada">
                FACTURA ANULADA
            </div>
        @endif

        {{-- FOOTER --}}
        <div class="footer">
            Esta es una factura oficial emitida por la Cooperativa de Agua.  
            Conserve este documento como respaldo de su pago.
            <br>
            Impreso el: {{ $fechaImpresion }}
        </div>
    </div>

    <button class="print-button" onclick="window.print()">🖨️ Imprimir Factura</button>
</body>
</html>
