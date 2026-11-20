<?php

namespace App\Http\Controllers;

use App\Actions\ProcesarPagoAction;
use App\Models\Factura;
use App\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\PagoFacilService;

class PagoController extends Controller
{
    /**
     * Inyectamos el servicio de PagoFácil y la acción centralizada de pagos.
     * Laravel resuelve ambas dependencias automáticamente.
     */
    public function __construct(
        protected PagoFacilService   $pagoService,
        protected ProcesarPagoAction $procesarPago
    ) {}

    /**
     * Muestra el HISTORIAL de pagos, paginado y con filtros.
     */
    public function index(Request $request)
    {
        $query = Pago::with([
            'factura:id,periodo,conexion_id',
            'factura.conexion:id,codigo_medidor,afiliado_id',
            'factura.conexion.afiliado:id,nombre_completo,ci',
            'usuarioRegistrado:id,name',
        ]);

        // Filtro por Rango de Fechas
        $query->when(
            $request->filled('fecha_inicio') && $request->filled('fecha_fin'),
            function ($q) use ($request) {
                try {
                    $inicio = Carbon::parse($request->fecha_inicio)->startOfDay();
                    $fin    = Carbon::parse($request->fecha_fin)->endOfDay();
                    $q->whereBetween('fecha_pago', [$inicio, $fin]);
                } catch (\Exception $e) {
                    // Ignorar fechas inválidas silenciosamente
                }
            }
        );

        // Búsqueda por Afiliado / Medidor
        $query->when($request->input('search'), function ($q, $search) {
            $q->whereHas('factura.conexion.afiliado', function ($aq) use ($search) {
                $aq->where('nombre_completo', 'like', "%{$search}%")
                   ->orWhere('ci', 'like', "%{$search}%");
            })->orWhereHas('factura.conexion', function ($cq) use ($search) {
                $cq->where('codigo_medidor', 'like', "%{$search}%");
            });
        });

        // Filtro por Forma de Pago
        $query->when($request->input('forma_pago'), function ($q, $forma) {
            $q->where('forma_pago', $forma);
        });

        return Inertia::render('Pagos/Index', [
            'pagos'   => $query->orderBy('fecha_pago', 'desc')
                               ->orderBy('id', 'desc')
                               ->paginate(10)
                               ->withQueryString(),
            'filters' => $request->only('fecha_inicio', 'fecha_fin', 'search', 'forma_pago'),
        ]);
    }

    /**
     * Muestra el formulario para registrar un pago en Efectivo o QR.
     */
    public function create(Request $request)
    {
        $request->validate(['factura_id' => 'required|exists:facturas,id']);
        $factura = Factura::with('conexion.afiliado')->findOrFail($request->factura_id);

        if ($factura->estado !== Factura::ESTADO_IMPAGA) {
            return redirect()->route('facturas.show', $factura->id)
                ->withErrors(['error_general' => "Esta factura ya está '{$factura->estado}' y no admite más pagos."]);
        }

        // Calcular saldo real en caso de discrepancia
        $saldoPendiente = (float) $factura->deuda_pendiente;
        if ($saldoPendiente <= 0) {
            $saldoPendiente = (float) $factura->monto_total - (float) $factura->pagos()->sum('monto_pagado');
            if ($saldoPendiente <= 0) {
                $factura->update(['estado' => Factura::ESTADO_PAGADO, 'deuda_pendiente' => 0]);
                return redirect()->route('facturas.show', $factura->id)
                    ->with('info', 'La factura ya se encontraba pagada.');
            }
        }

        return Inertia::render('Pagos/Create', [
            'factura'        => $factura,
            'saldoPendiente' => $saldoPendiente,
        ]);
    }

    /**
     * Guarda un nuevo pago en Efectivo.
     * Delega toda la lógica transaccional a ProcesarPagoAction.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'factura_id' => 'required|exists:facturas,id',
            'fecha_pago' => 'required|date|before_or_equal:today',
            'forma_pago' => 'required|string|in:Efectivo,QR',
            'referencia' => 'nullable|string|max:255',
        ]);

        try {
            $pago = $this->procesarPago->execute(
                facturaId:  $validated['factura_id'],
                formaPago:  $validated['forma_pago'],
                referencia: $validated['referencia'] ?? null,
                usuarioId:  Auth::id()
            );

            return redirect()->route('facturas.show', $validated['factura_id'])
                ->with('success', '✅ Pago registrado por Bs ' . number_format($pago->monto_pagado, 2) . '. Factura marcada como pagada.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('PagoController@store: Error al registrar pago.', [
                'error'   => $e->getMessage(),
                'request' => $request->all(),
            ]);
            return redirect()->back()
                ->withErrors(['error_general' => 'Ocurrió un error inesperado al registrar el pago.'])
                ->withInput();
        }
    }

    /**
     * Muestra el detalle de un PAGO específico.
     */
    public function show($id)
    {
        $pago = Pago::with([
            'factura.conexion.afiliado',
            'usuarioRegistrado',
        ])->findOrFail($id);

        return Inertia::render('Pagos/Show', [
            'pago' => $pago,
        ]);
    }

    /**
     * Anula un pago y revierte la factura a estado 'impaga'.
     */
    public function anular(Request $request, $id)
    {
        $pago = Pago::findOrFail($id);

        try {
            DB::transaction(function () use ($pago) {
                $factura = $pago->factura;

                // Revertir la Factura
                $factura->update([
                    'estado'          => Factura::ESTADO_IMPAGA,
                    'deuda_pendiente' => $factura->monto_total,
                    'fecha_pago'      => null,
                ]);

                // Eliminar el Pago
                $pago->delete();
            });

            // Re-evaluar estado financiero del afiliado después de la transacción
            $factura = Factura::with('conexion')->find($pago->factura_id);
            if ($factura?->conexion?->afiliado_id) {
                \App\Models\Afiliado::verificarEstadoFinanciero($factura->conexion->afiliado_id);
            }

        } catch (\Exception $e) {
            Log::error('PagoController@anular: Error al anular pago.', [
                'error'   => $e->getMessage(),
                'pago_id' => $id,
            ]);
            return redirect()->back()->withErrors(['error_general' => 'Error al anular el pago.']);
        }

        return redirect()->route('facturas.show', $pago->factura_id)
            ->with('success', '✅ Pago anulado. La factura está "impaga" de nuevo.');
    }

    /**
     * Historial de pagos del afiliado autenticado (rol Usuario).
     */
    public function miHistorial(Request $request)
    {
        $user = Auth::user();

        if (!$user->afiliado_id) {
            return Inertia::render('Usuario/HistorialPagos', [
                'pagos'   => ['data' => []],
                'filters' => [],
                'error'   => 'Tu cuenta no está asociada a ningún afiliado.',
            ]);
        }

        $query = Pago::with([
            'factura:id,periodo,monto_total',
            'usuarioRegistrado:id,name',
        ])->whereHas('factura', function ($fq) use ($user) {
            $fq->whereHas('conexion', function ($cq) use ($user) {
                $cq->where('afiliado_id', $user->afiliado_id);
            });
        });

        return Inertia::render('Usuario/HistorialPagos', [
            'pagos'   => $query->orderBy('fecha_pago', 'desc')
                               ->paginate(15)
                               ->withQueryString(),
            'filters' => $request->only([]),
            'error'   => null,
        ]);
    }

    // ---------------------------------------------------------------
    // Integración PagoFácil (QR)
    // ---------------------------------------------------------------

    /**
     * Genera la imagen QR para una factura y la devuelve al Frontend.
     */
    public function generarQr(Request $request)
    {
        $request->validate(['factura_id' => 'required|exists:facturas,id']);

        $factura = Factura::with('conexion.afiliado')->findOrFail($request->factura_id);

        if ($factura->estado !== Factura::ESTADO_IMPAGA) {
            return response()->json(['status' => 'error', 'message' => 'La factura ya está pagada.']);
        }

        $afiliado  = $factura->conexion->afiliado;
        $resultado = $this->pagoService->generarQR($factura, $afiliado);

        if ($resultado['success']) {
            return response()->json([
                'status'         => 'ok',
                'qr_image'       => $resultado['qr_image'],
                'payment_number' => $resultado['payment_number'],
                'monto'          => $factura->deuda_pendiente,
            ]);
        }

        return response()->json(['status' => 'error', 'message' => $resultado['message']]);
    }

    /**
     * Verifica el estado de un pago QR consultando a PagoFácil.
     * Si fue pagado, delega el registro a ProcesarPagoAction.
     */
    public function verificarQr(Request $request)
    {
        $paymentNumber = $request->payment_number;

        if (!$paymentNumber) {
            return response()->json(['status' => 'error', 'message' => 'Falta payment_number.']);
        }

        // Si ya existe el pago en nuestra BD, responder de inmediato (idempotente)
        if (Pago::where('referencia', $paymentNumber)->exists()) {
            return response()->json(['status' => 'pagado', 'origen' => 'local']);
        }

        // Consultar al banco
        $respuesta = $this->pagoService->consultarTransaccion($paymentNumber);

        if (!isset($respuesta['values'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Respuesta inválida del banco.',
            ]);
        }

        $estadoPago = $respuesta['values']['paymentStatus'] ?? 0;
        // Estado 2 = Pago exitoso en PagoFácil
        $esPagado   = ($estadoPago == 2);

        if ($esPagado) {
            try {
                // Extraer el ID de factura del formato: "prefijo_facturaId_suffix"
                $partes = explode('_', $paymentNumber);
                if (count($partes) < 2) {
                    throw new \RuntimeException("Formato de referencia inválido: {$paymentNumber}");
                }

                $facturaId = (int) $partes[1];

                // ProcesarPagoAction maneja la idempotencia, transacción y bloqueo
                $this->procesarPago->execute(
                    facturaId:  $facturaId,
                    formaPago:  'QR',
                    referencia: $paymentNumber,
                    usuarioId:  Auth::id()
                );

                return response()->json(['status' => 'pagado']);

            } catch (\Illuminate\Validation\ValidationException $e) {
                // La factura ya estaba pagada por otro proceso (carrera resuelta)
                return response()->json(['status' => 'pagado', 'origen' => 'ya_procesado']);
            } catch (\Exception $e) {
                Log::error('PagoController@verificarQr: Error al guardar pago QR.', [
                    'error'          => $e->getMessage(),
                    'payment_number' => $paymentNumber,
                ]);
                return response()->json(['status' => 'error', 'message' => 'Error interno al registrar.']);
            }
        }

        // Respuesta informativa para el Frontend mientras espera
        $mensajeEstado = match ($estadoPago) {
            1       => 'Esperando pago...',
            5       => 'En Revisión (Datos no coinciden con el banco)',
            default => 'Pendiente',
        };

        return response()->json([
            'status'       => 'pendiente',
            'estado_banco' => $estadoPago,
            'mensaje'      => $mensajeEstado,
        ]);
    }

    /**
     * Webhook de PagoFácil: recibe confirmación automática del banco.
     * Delega el registro a ProcesarPagoAction.
     */
    public function callbackPagoFacil(Request $request)
    {
        try {
            $datos = $request->all();
            Log::info('Webhook PagoFácil recibido:', $datos);

            $paymentNumber = $datos['PedidoID'] ?? null;
            $estado        = $datos['Estado']   ?? null;

            // Solo procesamos pagos exitosos
            if ($paymentNumber && ($estado == 2 || $estado === 'COMPLETED')) {
                $partes    = explode('_', $paymentNumber);
                $facturaId = isset($partes[1]) ? (int) $partes[1] : null;

                if ($facturaId) {
                    try {
                        // ProcesarPagoAction garantiza idempotencia y transacción segura
                        $this->procesarPago->execute(
                            facturaId:  $facturaId,
                            formaPago:  'QR',
                            referencia: $paymentNumber,
                            usuarioId:  1 // Usuario sistema para webhooks
                        );
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        // Factura ya pagada = webhook llegó dos veces. Es normal, no es error.
                        Log::info("Webhook: Factura #{$facturaId} ya estaba pagada. Se ignora duplicado.");
                    }
                }
            }

            // Respuesta OBLIGATORIA para que PagoFácil sepa que recibimos el mensaje
            return response()->json([
                'error'   => 0,
                'status'  => 1,
                'message' => 'Pago recibido correctamente',
                'values'  => true,
            ]);

        } catch (\Exception $e) {
            Log::error('Webhook PagoFácil - Error inesperado:', ['error' => $e->getMessage()]);

            return response()->json([
                'error'   => 1,
                'status'  => 1,
                'message' => 'Error interno',
                'values'  => false,
            ]);
        }
    }

    public function validarCobroSeguro(Request $request)
{
    if (!Auth::user()->hasAnyRole(['Administrador', 'Secretaria'])) {
        abort(403, 'Solo Administrador o Secretaria pueden registrar pagos.');
    }

    $validated = $request->validate([
        'factura_id' => ['required', 'integer', 'exists:facturas,id'],
        'forma_pago' => ['required', 'string', 'in:Efectivo,QR'],
    ]);

    $factura = Factura::findOrFail($validated['factura_id']);
    if ($factura->estado !== Factura::ESTADO_IMPAGA || (float) $factura->deuda_pendiente <= 0) {
        abort(422, 'La factura no está impaga o no tiene deuda pendiente mayor a cero.');
    }

    // El monto se obtiene exclusivamente de la factura en el servidor (sin confiar en el cliente)
    $pago = $this->procesarPago->execute(
        facturaId: $factura->id,
        formaPago: $validated['forma_pago'],
        usuarioId: Auth::id()
    );

    return response()->json([
        'status'       => 'ok',
        'monto_pagado' => $pago->monto_pagado,
    ], 201);
}
}