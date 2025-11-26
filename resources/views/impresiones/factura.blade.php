<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>
        Recibo - F-{{ str_pad($factura->id, 6, '0', STR_PAD_LEFT) }}
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Fuente Poppins --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Poppins', sans-serif; }

        @media print {
            .bg-cyan-50,
            .bg-cyan-100,
            .bg-blue-50,
            .bg-gradient-to-r,
            .bg-gray-50 {
                background-color: #ffffff !important;
            }
            .text-cyan-600,
            .text-cyan-700,
            .text-blue-600,
            .text-green-700,
            .text-red-700 {
                color: #000000 !important;
            }
            .border-cyan-200,
            .border,
            .border-yellow-500,
            .border-red-200 {
                border-color: #e5e7eb !important;
            }
            .no-print {
                display: none !important;
            }
            body,
            .print\:shadow-none {
                box-shadow: none !important;
            }
            .print\:bg-white {
                background: #ffffff !important;
            }
            .print\:text-black {
                color: #000000 !important;
            }
            .print\:border-gray {
                border-color: #d1d5db !important;
            }
            .print-logo {
                filter: grayscale(100%) opacity(0.85);
            }
            .print-line {
                background: #e5e7eb !important;
                height: 1px !important;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-cyan-50 min-h-screen p-3 md:p-4 print:bg-white">

<div class="max-w-3xl mx-auto bg-white shadow-md rounded-xl overflow-hidden print:shadow-none print:bg-white">

    {{-- Encabezado (pantalla) --}}
    <div class="bg-gradient-to-r from-cyan-500 to-blue-600 text-white px-3 py-3 md:px-4 md:py-3 print:hidden">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center space-x-2">
                <div class="bg-white p-1 rounded-full">
                    <img
                        src="{{ asset('storage/img/AGUA CABEZAS.png') }}"
                        alt="Logo Agua Cabezas"
                        class="w-8 h-8 md:w-10 md:h-10 object-contain print-logo"
                    >
                </div>
                <div>
                    <h1 class="text-sm md:text-base font-bold leading-tight uppercase">
                        Asociación de Beneficiarios de Agua Cabezas
                    </h1>
                    <p class="text-[10px] opacity-90">
                        Servicio de Agua Potable - Cabezas
                    </p>
                </div>
            </div>

            <div class="text-right">
                <p class="text-[10px] uppercase tracking-wide opacity-80">
                    Factura
                </p>
                <p class="text-xl font-bold leading-tight">
                    FACTURA
                </p>
                <p class="text-[11px] mt-0.5">
                    Cuenta de factura
                    <span class="font-semibold">
                        F-{{ str_pad($factura->id, 6, '0', STR_PAD_LEFT) }}
                    </span>
                </p>
            </div>
        </div>
    </div>

    {{-- Encabezado impresión --}}
    <div class="hidden print:flex print:px-3 print:py-2 print:border-b print:items-center print:justify-between">
        <div class="flex items-center space-x-2">
            <img
                src="{{ asset('storage/img/AGUA CABEZAS.png') }}"
                alt="Logo Agua Cabezas"
                class="w-7 h-7 object-contain print-logo"
            >
            <div>
                <p class="font-bold text-[11px] uppercase">
                    Asociación de Beneficiarios de Agua Cabezas
                </p>
                <p class="text-[10px]">
                    Servicio de Agua Potable
                </p>
            </div>
        </div>
        <div class="text-right text-[10px]">
            <p class="font-bold">
                FACTURA
            </p>
            <p>
                Cuenta de Factura F-{{ str_pad($factura->id, 6, '0', STR_PAD_LEFT) }}
            </p>
        </div>
    </div>

    {{-- Línea --}}
    <div class="h-1 bg-gradient-to-r from-cyan-400 via-blue-500 to-cyan-400 print-line print:h-px"></div>

    {{-- Datos de la Asociación --}}
    <div class="px-3 py-2 md:px-4 md:py-3 print:px-3 text-[10px] text-gray-600 border-b print:border-gray">
        <p class="font-medium text-gray-800">
            Casa Matriz:
        </p>
        <p>Av. Benigno Vaca Nº S/N, Zona Barrio El Carmen</p>
        <p>Cabezas - Santa Cruz - Bolivia &middot; Celular: 78773687</p>
    </div>

    {{-- Afiliado + Factura --}}
    <div class="px-3 py-3 md:px-4 md:py-3 print:px-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] md:text-xs border-b print:border-gray">
        <div class="space-y-0.5">
            <p>
                <span class="font-medium">Afiliado:</span>
                {{ $factura->conexion->afiliado->nombre_completo ?? 'N/A' }}
            </p>
            <p>
                <span class="font-medium">CI:</span>
                {{ $factura->conexion->afiliado->ci ?? 'N/A' }}
            </p>
            <p>
                <span class="font-medium">Dirección:</span>
                {{ $factura->conexion->direccion ?? 'S/D' }}
            </p>
        </div>
        <div class="space-y-0.5 text-left sm:text-right">
            <p>
                <span class="font-medium">N° Factura:</span>
                F-{{ str_pad($factura->id, 6, '0', STR_PAD_LEFT) }}
            </p>
            <p>
                <span class="font-medium">Fecha emisión:</span>
                {{ $factura->fecha_emision ? \Carbon\Carbon::parse($factura->fecha_emision)->format('d/m/Y') : 'N/A' }}
            </p>
            <p>
                <span class="font-medium">Período:</span>
                {{ $factura->periodo }}
            </p>
        </div>
    </div>

    {{-- Conexión resumida --}}
    <div class="px-3 py-2 md:px-4 md:py-2 print:px-3 text-[11px] md:text-xs border-b print:border-gray">
        <div class="flex flex-wrap gap-x-4 gap-y-1">
            <p>
                <span class="font-medium">Medidor:</span>
                <span class="font-mono">
                    {{ $factura->conexion->codigo_medidor ?? 'S/M' }}
                </span>
            </p>
            <p>
                <span class="font-medium">Tipo:</span>
                {{ ucfirst($factura->conexion->tipo_conexion ?? 'N/A') }}
            </p>
            <p>
                <span class="font-medium">Estado factura:</span>
                {{ strtoupper($factura->estado) }}
            </p>
        </div>
    </div>

    {{-- Detalle de la cuenta (tabla) --}}
    <div class="px-3 py-3 md:px-4 md:py-3 print:px-3">
        <div class="overflow-x-auto">
            <table class="w-full text-[11px] md:text-xs border-collapse">
                <thead>
                    <tr class="bg-cyan-50 text-cyan-800 print:bg-white print:text-black print:border-b print:border-gray">
                        <th class="text-left p-2 font-medium">
                            Descripción
                        </th>
                        <th class="text-center p-2 font-medium whitespace-nowrap">
                            Lect. ant.
                        </th>
                        <th class="text-center p-2 font-medium whitespace-nowrap">
                            Lect. act.
                        </th>
                        <th class="text-center p-2 font-medium whitespace-nowrap">
                            Consumo (m³)
                        </th>
                        <th class="text-right p-2 font-medium whitespace-nowrap">
                            Importe (Bs)
                        </th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    <tr class="border-b print:border-gray">
                        <td class="p-2">
                            Consumo de agua período {{ $factura->periodo }}
                        </td>
                        <td class="text-center p-2 font-mono">
                            {{ number_format($factura->lectura?->lectura_anterior, 2) }}
                        </td>
                        <td class="text-center p-2 font-mono">
                            {{ number_format($factura->lectura?->lectura_actual, 2) }}
                        </td>
                        <td class="text-center p-2 font-mono">
                            {{ number_format($consumo, 2) }}
                        </td>
                        <td class="text-right p-2 font-mono">
                            {{ number_format($factura->monto_total, 2) }}
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    @if(!is_null($montoBase))
                        <tr class="text-[10px] text-gray-600 print:text-black">
                            <td colspan="4" class="p-2 text-right">
                                Subtotal por consumo
                            </td>
                            <td class="p-2 text-right font-semibold">
                                Bs {{ number_format($montoBase, 2) }}
                            </td>
                        </tr>
                        <tr class="text-[10px] text-gray-600 print:text-black">
                            <td colspan="4" class="p-2 text-right">
                                Descuento
                                @if($descuentoEstimado > 0 && $porcentajeDescuento > 0)
                                    ({{ $porcentajeDescuento }}% Adulto Mayor)
                                @endif
                            </td>
                            <td class="p-2 text-right font-semibold">
                                @if($descuentoEstimado > 0)
                                    - Bs {{ number_format($descuentoEstimado, 2) }}
                                @else
                                    Bs 0.00
                                @endif
                            </td>
                        </tr>
                    @endif
                    <tr class="bg-gray-50 print:bg-white font-bold">
                        <td colspan="4" class="p-2 text-right print:text-black">
                            TOTAL FACTURA:
                        </td>
                        <td class="p-2 text-right text-cyan-700 print:text-black">
                            Bs {{ number_format($factura->monto_total, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Resumen del pago (una sola tarjeta compacta) --}}
    <div class="px-3 pb-3 md:px-4 md:pb-4 print:px-3">
        <div class="border border-cyan-200 rounded-lg p-3 text-[11px] md:text-xs space-y-1 print:border-gray">
            <div class="flex justify-between">
                <span>Total de la factura:</span>
                <span class="font-semibold">
                    Bs {{ number_format($factura->monto_total, 2) }}
                </span>
            </div>
            <div class="flex justify-between text-gray-700">
                <span>Total pagado a la fecha:</span>
                <span class="font-semibold">
                    Bs {{ number_format($totalPagado, 2) }}
                </span>
            </div>
            <div class="flex justify-between text-gray-700">
                <span>Saldo pendiente:</span>
                <span class="font-semibold">
                    Bs {{ number_format($saldo, 2) }}
                </span>
            </div>

            <hr class="my-2 border-dashed border-gray-300">

            <div class="flex justify-between text-gray-800">
                <span>Monto pagado en esta Factura:</span>
                <span class="font-bold text-emerald-700 print:text-black">
                    Bs {{ number_format($ultimoPago?->monto_pagado ?? $totalPagado, 2) }}
                </span>
            </div>

            @if($ultimoPago)
                <div class="flex justify-between">
                    <span>Fecha de pago:</span>
                    <span>
                        {{ $ultimoPago->fecha_pago ? \Carbon\Carbon::parse($ultimoPago->fecha_pago)->format('d/m/Y') : 'N/A' }}
                    </span>
                </div>
                <div class="flex justify-between text-[10px] text-gray-600">
                    <span>Forma / referencia:</span>
                    <span class="text-right">
                        {{ $ultimoPago->forma_pago }}
                        @if($ultimoPago->referencia)
                            (Ref: {{ $ultimoPago->referencia }})
                        @endif
                    </span>
                </div>
            @endif
        </div>
    </div>

    {{-- Footer --}}
    <div class="bg-gradient-to-r from-cyan-500 to-blue-600 text-white px-3 py-2 md:px-4 md:py-2 text-[10px] text-center print:hidden">
        <p>
            Recibo válido como comprobante de pago de la factura
            F-{{ str_pad($factura->id, 6, '0', STR_PAD_LEFT) }}.
            Impreso el {{ $fechaImpresion }}.
        </p>
    </div>
    <div class="hidden print:block print:px-3 print:py-2 print:text-center print:text-[10px] print:text-gray-600 print:border-t print:border-gray">
        <p>
            Recibo válido como comprobante de pago correspondiente a la factura
            F-{{ str_pad($factura->id, 6, '0', STR_PAD_LEFT) }}. Impreso el {{ $fechaImpresion }}.
        </p>
    </div>
</div>

{{-- Botón imprimir --}}
<div class="text-center mt-4 no-print">
    <button
        onclick="window.print()"
        class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold py-2.5 px-7 rounded-full shadow-md text-[13px] md:text-sm transition transform hover:scale-105"
    >
        🧾 Imprimir Factura
    </button>
</div>

</body>
</html>
