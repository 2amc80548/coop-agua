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
                                 ->lockForUpdate() // ¡Bloquea la fila para evitar pagos dobles!
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
        
        $factura = Factura::with('conexion.afiliado')->findOrFail($request->factura_id);

        // Validar que no esté pagada
        if ($factura->estado !== 'impaga') {
            return response()->json(['status' => 'error', 'message' => 'La factura ya está pagada o anulada.']);
        }

        // Usamos el servicio que inyectamos
        $resultado = $this->pagoService->generarQR($factura, $factura->conexion->afiliado);

        if ($resultado['success']) {
            return response()->json([
                'status' => 'ok',
                'qr_image' => $resultado['qr_image'],      // La imagen Base64
                'payment_number' => $resultado['payment_number'], // El ID "grupo05cc_..."
                'monto' => $factura->deuda_pendiente
            ]);
        } else {
            return response()->json(['status' => 'error', 'message' => $resultado['message']]);
        }
    }

    /**
     * Verifica si el cliente ya pagó desde su celular
     * Se llama cada 5 segundos desde el navegador
     */
    public function verificarQr(Request $request)
    {
        $paymentNumber = $request->payment_number; // Recibimos "grupo05cc_123"

        // 1. Consultar al Banco (Pago Fácil)
        $respuesta = $this->pagoService->consultarTransaccion($paymentNumber);

        // NOTA: Según tu PDF, debes revisar qué devuelve exactamente 'paymentStatus'.
        // Asumiremos que devuelve un string o número indicando éxito.
        // Si tienes dudas, usa: Log::info($respuesta); para ver qué llega.
        
        // Digamos que status == 2 es PAGADO (ajusta esto según tu prueba real)
        $estadoPago = $respuesta['values']['paymentStatus'] ?? null; // Ajusta la ruta del JSON

        // Verificamos si el estado indica éxito (Ajustar condición según respuesta real)
        // A veces devuelven el string "COMPLETED" o "PROCESSED"
        $pagadoEnBanco = ($estadoPago == 2 || $estadoPago == 5 || $estadoPago == 'COMPLETED');

        if ($pagadoEnBanco) {
            
            // ¡EL DINERO ESTÁ EN EL BANCO! AHORA GUARDAMOS EN TU BD
            
            // Extraer ID real de la factura
            $partes = explode('_', $paymentNumber); 
            // El formato es: grupo05cc_IDFACTURA_UNIQID
            // $partes[0] = grupo05cc
            // $partes[1] = IDFACTURA (El que queremos)
            // $partes[2] = UNIQID
            $facturaId = $partes[1];

            // Usamos Transaction igual que en tu método store() para seguridad
            try {
                DB::transaction(function () use ($facturaId, $paymentNumber, $respuesta) {
                    
                    // Verificar si YA lo registramos antes (para evitar duplicados por el polling)
                    $yaExiste = Pago::where('referencia', $paymentNumber)->exists();
                    if ($yaExiste) return; // Si ya existe, no hacemos nada, solo retornamos éxito abajo

                    $factura = Factura::lockForUpdate()->find($facturaId);

                    if ($factura && $factura->estado === 'impaga') {
                        // Crear el Pago
                        Pago::create([
                            'factura_id'     => $factura->id,
                            'monto_pagado'   => $factura->deuda_pendiente, // O $respuesta['values']['amount']
                            'fecha_pago'     => Carbon::now(),
                            'forma_pago'     => 'QR',
                            'referencia'     => $paymentNumber, // Guardamos el ID de transacción
                            'registrado_por' => Auth::id(),
                        ]);

                        // Actualizar Factura
                        $factura->update([
                            'estado'          => 'pagado',
                            'deuda_pendiente' => 0,
                            'fecha_pago'      => Carbon::now()
                        ]);
                    }
                });

                return response()->json(['status' => 'pagado']);

            } catch (\Exception $e) {
                Log::error("Error guardando pago QR: " . $e->getMessage());
                return response()->json(['status' => 'error', 'message' => 'Error interno al guardar']);
            }
        }

        // Si no ha pagado aún
        return response()->json(['status' => 'pendiente']);
    }
  
    // public function edit($id) { abort(405, 'Los pagos no se editan, se anulan.'); }
    // public function update(Request $request, $id) { abort(405, 'Los pagos no se editan, se anulan.'); }
    // public function destroy($id) { abort(405, 'Los pagos no se eliminan, se anulan.'); }
}