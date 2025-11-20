<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aviso de Cobranza - {{ $lectura->periodo }}</title>

    {{-- Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Fuente Poppins --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Poppins', sans-serif; }

        @media print {
            /* Imprimir con poco color (más amigable con tinta) */
            .bg-gradient-to-r,
            .bg-cyan-50,
            .bg-blue-50,
            .bg-yellow-50,
            .bg-red-50 {
                background-color: #ffffff !important;
            }

            .text-cyan-600,
            .text-cyan-700,
            .text-blue-600,
            .text-red-700,
            .text-yellow-700 {
                color: #374151 !important; /* gris oscuro */
            }

            .border,
            .border-cyan-200,
            .border-yellow-500,
            .border-red-200 {
                border-color: #d1d5db !important; /* gris */
            }

            .no-print {
                display: none !important;
            }

            .print-logo {
                filter: grayscale(100%) opacity(0.85);
            }

            .print-line {
                background: #e5e7eb !important;
                height: 1px !important;
            }

            body {
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-cyan-50 min-h-screen p-4 md:p-6 print:bg-white">

<div class="max-w-3xl mx-auto bg-white shadow-xl rounded-xl overflow-hidden print:shadow-none print:bg-white">

    {{-- Encabezado bonito (pantalla) --}}
    <div class="bg-gradient-to-r from-cyan-500 to-blue-600 text-white p-4 md:p-5 print:hidden">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="bg-white p-1.5 rounded-full">
                    {{-- TU LOGO --}}
                    <img
                        src="{{ asset('storage/img/AGUA CABEZAS.png') }}"
                        alt="Logo Agua Cabezas"
                        class="w-10 h-10 object-contain print-logo"
                    >
                </div>
                <div>
                    <h1 class="text-lg md:text-xl font-bold leading-tight">
                        ASOCIACIÓN DE BENEFICIARIOS DE AGUA CABEZAS
                    </h1>
                    <p class="text-[11px] opacity-90">
                        Servicio de Agua Potable - Cabezas
                    </p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-xs uppercase tracking-wide opacity-80">Aviso</p>
                <p class="text-base md:text-lg font-bold">
                    AVISO DE LECTURA Y COBRANZA
                </p>
                <p class="text-xs mt-1">
                    Período: <span class="font-semibold">{{ $lectura->periodo }}</span>
                </p>
            </div>
        </div>
    </div>

    {{-- Encabezado simple para impresión --}}
    <div class="hidden print:flex print:p-4 print:border-b print:items-center print:justify-between print:text-gray-800">
        <div class="flex items-center space-x-2">
            <img
                src="{{ asset('storage/img/AGUA CABEZAS.png') }}"
                alt="Logo Agua Cabezas"
                class="w-8 h-8 object-contain print-logo"
            >
            <div>
                <p class="font-bold text-sm">
                    ASOCIACIÓN DE BENEFICIARIOS DE AGUA CABEZAS
                </p>
                <p class="text-[10px]">
                    Servicio de Agua Potable
                </p>
            </div>
        </div>
        <div class="text-right">
            <p class="font-bold text-[11px] uppercase">
                Aviso de lectura y cobranza
            </p>
            <p class="text-[10px]">
                Período: {{ $lectura->periodo }}
            </p>
        </div>
    </div>

    {{-- Línea decorativa --}}
    <div class="h-1 bg-gradient-to-r from-cyan-400 via-blue-500 to-cyan-400 print-line print:h-px"></div>

    {{-- Datos de la Asociación --}}
    <div class="p-4 md:p-5 print:p-4 text-[11px] text-gray-600 border-b print:border-gray-300">
        <p class="font-medium text-gray-800">Casa Matriz:</p>
        <p>Av. Benigno Vaca Nº S/N, Zona Barrio El Carmen</p>
        <p>Cabezas - Santa Cruz - Bolivia</p>
        <p>Celular: 78773687</p>
    </div>

    {{-- Datos del Afiliado --}}
    <div class="p-4 md:p-5 print:p-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs md:text-sm border-b print:border-gray-300">
        <div class="space-y-1">
            <p>
                <span class="font-medium">Afiliado:</span>
                {{ $lectura->conexion->afiliado->nombre_completo ?? 'N/A' }}
            </p>
            <p>
                <span class="font-medium">Dirección:</span>
                {{ $lectura->conexion->direccion ?? 'S/D' }}
            </p>
        </div>
        <div class="space-y-1">
            <p>
                <span class="font-medium">Medidor:</span>
                <span class="font-mono">
                    {{ $lectura->conexion->codigo_medidor ?? 'S/M' }}
                </span>
            </p>
            <p>
                <span class="font-medium">Período:</span>
                <span class="font-semibold">
                    {{ $lectura->periodo }}
                </span>
            </p>
        </div>
    </div>

    {{-- Tabla de Lectura --}}
    <div class="p-4 md:p-5 print:p-4">
        <table class="w-full text-xs md:text-sm border-collapse border border-cyan-200 print:border-gray-300">
            <thead>
                <tr class="bg-cyan-50 text-cyan-800 print:bg-gray-50 print:text-gray-800">
                    <th class="p-2 text-left font-medium">Fecha de lectura</th>
                    <th class="p-2 text-center font-medium">Lect. anterior</th>
                    <th class="p-2 text-center font-medium">Lect. actual</th>
                    <th class="p-2 text-center font-medium">Consumo (m³)</th>
                </tr>
            </thead>
            <tbody class="text-center">
                <tr class="border-b print:border-gray-300">
                    <td class="p-2 text-left">
                        {{ \Carbon\Carbon::parse($lectura->fecha_lectura)->format('d/m/Y') }}
                    </td>
                    <td class="p-2 font-mono">
                        {{ number_format($lectura->lectura_anterior, 2) }}
                    </td>
                    <td class="p-2 font-mono">
                        {{ number_format($lectura->lectura_actual, 2) }}
                    </td>
                    <td class="p-2 font-bold text-cyan-700 print:text-gray-800">
                        {{ number_format($consumo, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- BLOQUE COMPACTO: Subtotal + Descuento + Total estimado --}}
    <div class="px-4 md:px-5 pb-4">
        <div
            class="border border-cyan-200 rounded-lg p-3 md:p-4 text-xs md:text-sm space-y-1 print:border-gray-300"
        >
            <div class="flex items-center justify-between">
                <span class="font-medium text-gray-700">
                    Subtotal por consumo
                </span>
                <span class="font-semibold text-gray-900">
                    Bs {{ number_format($montoEstimado + $descuentoAplicado, 2) }}
                </span>
            </div>

            @if($descuentoAplicado > 0)
                <div class="flex items-center justify-between text-emerald-700 print:text-gray-700">
                    <span class="text-[11px]">
                        Descuento Adulto Mayor ({{ $tarifa->descuento_adulto_mayor_pct }}%)
                    </span>
                    <span class="font-semibold">
                        - Bs {{ number_format($descuentoAplicado, 2) }}
                    </span>
                </div>
            @else
                <div class="flex items-center justify-between text-gray-600">
                    <span class="text-[11px]">
                        Descuento aplicado
                    </span>
                    <span class="font-semibold">
                        Bs 0.00
                    </span>
                </div>
            @endif

            <div class="border-t border-dashed border-gray-300 mt-2 pt-2 flex items-center justify-between">
                <span class="font-bold text-gray-800 text-sm">
                    Total estimado a pagar
                </span>
                <span class="font-extrabold text-base md:text-lg text-yellow-700 print:text-gray-800">
                    Bs {{ number_format($montoEstimado, 2) }}
                </span>
            </div>
        </div>

        <p class="mt-2 text-[11px] text-gray-500">
            Este monto es referencial y puede variar en la factura final según cargos fijos,
            cuotas de socio, reconexiones u otros conceptos adicionales.
        </p>
    </div>

    {{-- Observación y nota --}}
    <div class="px-4 md:px-5 pb-4 text-xs md:text-sm text-gray-600 space-y-3">
        @if($lectura->observacion)
            <div class="italic">
                <p>
                    <span class="font-semibold">Observación:</span>
                    {{ $lectura->observacion }}
                </p>
            </div>
        @endif

        <div class="text-[11px] md:text-xs text-gray-500">
            <p>
                Este es un <strong>aviso preventivo</strong> y no una factura oficial.
                El monto mostrado es un estimado y su factura puede incluir otros
                conceptos que serán detallados en el documento de cobro correspondiente.
            </p>
        </div>
    </div>

    {{-- Footer (pantalla) --}}
    <div class="bg-gradient-to-r from-cyan-500 to-blue-600 text-white p-3 md:p-4 text-[11px] md:text-xs text-center print:hidden">
        <p>
            Registrado por:
            <span class="font-semibold">
                {{ $lectura->usuarioRegistrado?->name ?? 'Sistema' }}
            </span>
            &nbsp;|&nbsp;
            Impreso el:
            <span class="font-semibold">
                {{ $fechaImpresion }}
            </span>
        </p>
    </div>

    {{-- Footer impresión --}}
    <div class="hidden print:block print:p-3 print:text-center print:text-[10px] print:text-gray-600 print:border-t print:border-gray-300">
        <p>
            Registrado por:
            {{ $lectura->usuarioRegistrado?->name ?? 'Sistema' }}
            &nbsp;|&nbsp;
            Impreso el:
            {{ $fechaImpresion }}
        </p>
    </div>
</div>

{{-- Botón Imprimir (solo pantalla) --}}
<div class="text-center mt-6 no-print">
    <button
        onclick="window.print()"
        class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold py-3 px-8 rounded-full shadow-lg text-sm md:text-base transition transform hover:scale-105"
    >
        🖨️ Imprimir aviso
    </button>
</div>

</body>
</html>
