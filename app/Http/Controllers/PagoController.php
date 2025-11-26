<?php

namespace App\Http\Controllers;

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
    // 1. Declaramos la variable para el servicio
    protected $pagoService;

    // 2. Creamos el constructor para inyectarlo
    public function __construct(PagoFacilService $pagoService)
    {
        $this->pagoService = $pagoService;
    }
    /**
     * Muestra el HISTORIAL de pagos, paginado y con filtros.
     */
    public function index(Request $request)
    {
        $query = Pago::with([
            'factura:id,periodo,conexion_id',
            'factura.conexion:id,codigo_medidor,afiliado_id',
            'factura.conexion.afiliado:id,nombre_completo,ci',
            'usuarioRegistrado:id,name'
        ]);

        // Filtro por Rango de Fechas
        $query->when($request->filled('fecha_inicio') && $request->filled('fecha_fin'), function ($q) use ($request) {
             try {
                $inicio = Carbon::parse($request->fecha_inicio)->startOfDay();
                $fin = Carbon::parse($request->fecha_fin)->endOfDay();
                $q->whereBetween('fecha_pago', [$inicio, $fin]);
             } catch (\Exception $e) { /* Ignorar fechas inválidas */ }
        });

        // Búsqueda por Afiliado/Medidor
        $query->when($request->input('search'), function ($q, $search) {
            $q->whereHas('factura.conexion.afiliado', function ($afilQuery) use ($search) {
                $afilQuery->where('nombre_completo', 'like', "%{$search}%")
                          ->orWhere('ci', 'like', "%{$search}%");
            })->orWhereHas('factura.conexion', function ($conQuery) use ($search) {
                $conQuery->where('codigo_medidor', 'like', "%{$search}%");
            });
        });
        
        // Búsqueda por Forma de Pago
        $query->when($request->input('forma_pago'), function ($q, $forma) {
            $q->where('forma_pago', $forma);
        });

        return Inertia::render('Pagos/Index', [
            'pagos' => $query->orderBy('fecha_pago', 'desc')
                             ->orderBy('id', 'desc')
                             ->paginate(10)
                             ->withQueryString(),
            'filters' => $request->only('fecha_inicio', 'fecha_fin', 'search', 'forma_pago'),
        ]);
    }

    /**
     * Muestra el formulario para registrar un pago para UNA factura.
     */
    public function create(Request $request)
    {
        $request->validate(['factura_id' => 'required|exists:facturas,id']);
        $factura = Factura::with('conexion.afiliado')->findOrFail($request->factura_id);

        if ($factura->estado !== 'impaga') {
            return redirect()->route('facturas.show', $factura->id)
                           ->withErrors(['error_general' => 'Esta factura ya está '.$factura->estado.' y no admite más pagos.']);
        }

        $saldoPendiente = $factura->deuda_pendiente;
        if ($saldoPendiente <= 0) {
             // Lógica de seguridad por si la deuda es 0 pero el estado es impago
             $saldoPendiente = $factura->monto_total - $factura->pagos()->sum('monto_pagado');
             if ($saldoPendiente <= 0) {
                 $factura->update(['estado' => 'pagado', 'deuda_pendiente' => 0]);
                 return redirect()->route('facturas.show', $factura->id)->with('info', 'La factura ya se encontraba pagada.');
             }
        }

        return Inertia::render('Pagos/Create', [
            'factura' => $factura,
            'saldoPendiente' => $saldoPendiente,
        ]);
    }

    /**
     * Guarda el nuevo pago y actualiza la factura 
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
            $pago = null;
            DB::transaction(function () use ($validated, &$pago) {
                
                $factura = Factura::where('id', $validated['factura_id'])
                                 ->lockForUpdate() 
                                 ->first();

                if ($factura->estado !== 'impaga') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'factura_id' => 'Esta factura ya no está "impaga". (Estado actual: '.$factura->estado.')'
                    ]);
                }

                // El monto a pagar es la deuda total )
                $montoAPagar = $factura->deuda_pendiente;
                
                if ($montoAPagar <= 0) {
                     throw \Illuminate\Validation\ValidationException::withMessages([
                        'factura_id' => 'Esta factura no tiene deuda pendiente (Saldo: 0).'
                    ]);
                }

                // 1. Crear el Pago
                $pago = Pago::create([
                    'factura_id' => $factura->id,
                    'monto_pagado' => $montoAPagar, 
                    'fecha_pago' => $validated['fecha_pago'],
                    'forma_pago' => $validated['forma_pago'],
                    'referencia' => $validated['referencia'],
                    'registrado_por' => Auth::id(),
                ]);

                // 2. Actualizar la Factura
                $factura->update([
                    'estado' => 'pagado',
                    'deuda_pendiente' => 0, 
                    'fecha_pago' => $validated['fecha_pago']
                ]);

$facturaCheck = Factura::with('conexion')->find($validated['factura_id']);
                if ($facturaCheck && $facturaCheck->conexion) {
                     \App\Models\Afiliado::verificarEstadoFinanciero($facturaCheck->conexion->afiliado_id);
                }
                
            }); 


            return redirect()->route('facturas.show', $validated['factura_id'])
                           ->with('success', '✅ Pago registrado por Bs '.number_format($pago->monto_pagado, 2).'. Factura actualizada a "pagado".');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error al registrar pago:', ['error' => $e->getMessage(), 'request' => $request->all()]);
            return redirect()->back()
                           ->withErrors(['error_general' => 'Ocurrió un error inesperado al registrar el pago.'])
                           ->withInput();
        }
    }

    /**
     * Muestra el detalle de un PAGO específico (historial).
     */
    public function show($id)
    {
        $pago = Pago::with([
            'factura.conexion.afiliado',
            'usuarioRegistrado'
        ])->findOrFail($id);

        return Inertia::render('Pagos/Show', [ 
            'pago' => $pago,
        ]);
    }
    
    /**
     * Anula un pago (acción de Admin).
     */
    public function anular(Request $request, $id) 
    {
        $pago = Pago::findOrFail($id);
        
        try {
            DB::transaction(function () use ($pago) {
                $factura = $pago->factura;
                
                // 1. Revertir la Factura a 'impaga'
                $factura->update([
                    'estado' => 'impaga',
                    'deuda_pendiente' => $factura->monto_total, // Restaura la deuda completa
                    'fecha_pago' => null // Quita la fecha de pago
                ]);
                
                // 2. Eliminar el Pago
                $pago->delete();
            });

            $facturaCheck = Factura::with('conexion')->find($pago->factura_id);
        if ($facturaCheck && $facturaCheck->conexion) {
             \App\Models\Afiliado::verificarEstadoFinanciero($facturaCheck->conexion->afiliado_id);
        }

        } catch (\Exception $e) {
             Log::error('Error al anular pago:', ['error' => $e->getMessage(), 'pago_id' => $id]);
             return redirect()->back()->withErrors(['error_general' => 'Error al anular el pago.']);
        }
        
        // Redirige a la factura, que ahora está impaga de nuevo
        return redirect()->route('facturas.show', $pago->factura_id)
                       ->with('success', '✅ Pago anulado. La factura ahora está "impaga" de nuevo.');
    }


  public function miHistorial(Request $request)
    {
        $user = Auth::user();

        if (!$user->afiliado_id) {
            return Inertia::render('Usuario/HistorialPagos', [
                'pagos' => ['data' => []],
                'filters' => [],
                'error' => 'Tu cuenta de usuario no está asociada a ningún afiliado.'
            ]);
        }
        
        $afiliadoId = $user->afiliado_id;

        $query = Pago::with([
            'factura:id,periodo,monto_total',
            'usuarioRegistrado:id,name' 
        ])
        // Busca pagos donde la factura asociada...
        ->whereHas('factura', function ($facturaQuery) use ($afiliadoId) {
            // ...pertenece a una conexión...
            $facturaQuery->whereHas('conexion', function ($conexionQuery) use ($afiliadoId) {
                // ...que pertenece a ESTE afiliado.
                $conexionQuery->where('afiliado_id', $afiliadoId);
            });
        });

        return Inertia::render('Usuario/HistorialPagos', [ 
            'pagos' => $query->orderBy('fecha_pago', 'desc')
                             ->paginate(15)
                             ->withQueryString(),
            'filters' => $request->only([]), 
            'error' => null
        ]);
    }


    // ... tus otras funciones (miHistorial, anular, etc) ...

    /**
     * ------------------------------------------------------------
     * NUEVAS FUNCIONES PARA PAGO FÁCIL (QR)
     * ------------------------------------------------------------
     */

    /**
     * Genera la imagen QR y la devuelve al Frontend (sin guardar en BD aún)
     */
    public function generarQr(Request $request)
    {
        $request->validate(['factura_id' => 'required|exists:facturas,id']);
        
        // Cargar relaciones necesarias (conexion, afiliado)
        $factura = Factura::with('conexion.afiliado')->findOrFail($request->factura_id);

        if ($factura->estado !== 'impaga') {
            return response()->json(['status' => 'error', 'message' => 'Factura ya pagada.']);
        }

        $afiliado = $factura->conexion->afiliado; // Asegurar que esto no sea null

        $resultado = $this->pagoService->generarQR($factura, $afiliado);

        if ($resultado['success']) {
            return response()->json([
                'status' => 'ok',
                'qr_image' => $resultado['qr_image'],
                'payment_number' => $resultado['payment_number'],
                'monto' => $factura->deuda_pendiente
            ]);
        } else {
            return response()->json(['status' => 'error', 'message' => $resultado['message']]);
        }
    }

    public function verificarQr(Request $request)
    {
        $paymentNumber = $request->payment_number;
        if (!$paymentNumber) {
            return response()->json(['status' => 'error', 'message' => 'Falta payment_number']);
        }

        $yaPagadoLocalmente = Pago::where('referencia', $paymentNumber)->exists();
        
        if ($yaPagadoLocalmente) {
            return response()->json(['status' => 'pagado', 'origen' => 'local']);
        }
        // 1. Consultar al Banco
        $respuesta = $this->pagoService->consultarTransaccion($paymentNumber);

        // Verificación de seguridad para que no crashee si el banco falla
        if (!isset($respuesta['values'])) {
             return response()->json([
                 'status' => 'error', 
                 'message' => 'Respuesta inválida del banco',
                 'debug' => $respuesta // Para que veas qué llegó
             ]);
        }

        // Obtenemos el estado. Si no existe, asumimos 0 
        $estadoPago = $respuesta['values']['paymentStatus'] ?? 0;

        // LOGICA DE ESTADOS:
        // 1 = proceso 
        // 5 = Error/Revisión (datos no coinciden)
        // 2 = Éxito (Estándar PagoFácil al quitar el error del callback)
        
        $esPagado = ($estadoPago == 2);
                       // || $estadoPago == 5

        if ($esPagado) {
            try {
                $partes = explode('_', $paymentNumber);
                // Validación por si el paymentNumber no tiene el formato correcto
                if (count($partes) < 2) throw new \Exception("Formato de referencia inválido");
                
                $facturaId = $partes[1];

                DB::transaction(function () use ($facturaId, $paymentNumber) {
                    // Evitar duplicados
                    if (Pago::where('referencia', $paymentNumber)->exists()) return;

                    $factura = Factura::lockForUpdate()->find($facturaId);

                    if ($factura && $factura->estado === 'impaga') {
                        Pago::create([
                            'factura_id'     => $factura->id,
                            'monto_pagado'   => $factura->deuda_pendiente,
                            'fecha_pago'     => Carbon::now(),
                            'forma_pago'     => 'QR',
                            'referencia'     => $paymentNumber,
                            'registrado_por' => Auth::id() ?? 1, 
                        ]);

                        $factura->update([
                            'estado'          => 'pagado',
                            'deuda_pendiente' => 0,
                            'fecha_pago'      => Carbon::now()
                        ]);
                    }
                });

                return response()->json(['status' => 'pagado']);

            } catch (\Exception $e) {
                Log::error("Error guardando pago: " . $e->getMessage());
                return response()->json(['status' => 'error', 'message' => 'Error interno']);
            }
        }

        // Respuesta informativa para el frontend
        $mensaje = 'Pendiente';
        if ($estadoPago == 5) $mensaje = 'En Revisión (Datos no coinciden)';
        if ($estadoPago == 1) $mensaje = 'Esperando pago...';

        return response()->json([
            'status' => 'pendiente', 
            'estado_banco' => $estadoPago, // IMPORTANTE: Verás este número en la consola del navegador
            'mensaje' => $mensaje
        ]);
    }
    public function callbackPagoFacil(Request $request)
    {
        try {
            $datos = $request->all();
            Log::info('Callback PagoFácil Recibido:', $datos);

            $paymentNumber = $datos['PedidoID'] ?? null; 
            $estado = $datos['Estado'] ?? null; 

            // Validamos que sea un pago exitoso (2 o COMPLETED)
            if ($paymentNumber && ($estado == 2 || $estado == 'COMPLETED')) {
                
                $partes = explode('_', $paymentNumber);
                $facturaId = $partes[1] ?? null;

                if ($facturaId) {
                    DB::transaction(function () use ($facturaId, $paymentNumber) {
                        // Evitar duplicados
                        if (Pago::where('referencia', $paymentNumber)->exists()) return;

                        $factura = Factura::lockForUpdate()->find($facturaId);

                        if ($factura && $factura->estado === 'impaga') {
                            Pago::create([
                                'factura_id'     => $factura->id,
                                'monto_pagado'   => $factura->deuda_pendiente,
                                'fecha_pago'     => Carbon::now(),
                                'forma_pago'     => 'QR ', // Marca distintiva
                                'referencia'     => $paymentNumber,
                                'registrado_por' => 1, 
                            ]);

                            $factura->update([
                                'estado'          => 'pagado',
                                'deuda_pendiente' => 0,
                                'fecha_pago'      => Carbon::now()
                            ]);
                        }
                    });
                        $facturaCheck = Factura::with('conexion')->find($facturaId);
                           if ($facturaCheck && $facturaCheck->conexion) {
                      \App\Models\Afiliado::verificarEstadoFinanciero($facturaCheck->conexion->afiliado_id);
                    }
                }
            }

            // Respuesta OBLIGATORIA para que PagoFácil sepa que recibimos el mensaje
            return response()->json([
                "error" => 0,
                "status" => 1,
                "message" => "Pago recibido correctamente",
                "values" => true
            ]);

        } catch (\Exception $e) {
            Log::error('Error Callback: ' . $e->getMessage());
            return response()->json(["error" => 1, "status" => 1, "message" => "Error interno", "values" => false]);
        }
    }

}