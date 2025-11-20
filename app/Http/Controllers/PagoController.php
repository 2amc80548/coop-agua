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

class PagoController extends Controller
{

    /**
     * Muestra el HISTORIAL de pagos, paginado y con filtros.
     */
    public function index(Request $request)
    {
        $query = Pago::with([
            'factura:id,periodo,conexion_id',
            'factura.conexion:id,codigo_medidor,afiliado_id',
            'factura.conexion.afiliado:id,nombre_completo,ci', // ¡Cambiado a 'afiliado'!
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
                             ->paginate(20)
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
     * Guarda el nuevo pago y actualiza la factura (¡SIN PAGOS PARCIALES!)
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

                // El monto a pagar es la deuda total (REGLA: NO pagos parciales)
                $montoAPagar = $factura->deuda_pendiente;
                
                if ($montoAPagar <= 0) {
                     throw \Illuminate\Validation\ValidationException::withMessages([
                        'factura_id' => 'Esta factura no tiene deuda pendiente (Saldo: 0).'
                    ]);
                }

                // 1. Crear el Pago
                $pago = Pago::create([
                    'factura_id' => $factura->id,
                    'monto_pagado' => $montoAPagar, // ¡Usamos el monto de la BD!
                    'fecha_pago' => $validated['fecha_pago'],
                    'forma_pago' => $validated['forma_pago'],
                    'referencia' => $validated['referencia'],
                    'registrado_por' => Auth::id(),
                ]);

                // 2. Actualizar la Factura
                $factura->update([
                    'estado' => 'pagado',
                    'deuda_pendiente' => 0, // La deuda es cero
                    'fecha_pago' => $validated['fecha_pago']
                ]);
            }); // Fin transacción

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

        return Inertia::render('Pagos/Show', [ // ¡Vista nueva!
            'pago' => $pago,
        ]);
    }
    
    /**
     * Anula un pago (acción de Admin).
     * Esto revierte el pago Y la factura.
     */
    public function anular(Request $request, $id) // 'id' es el ID del PAGO
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
            'usuarioRegistrado:id,name' // El cajero que cobró
        ])
        // Busca pagos donde la factura asociada...
        ->whereHas('factura', function ($facturaQuery) use ($afiliadoId) {
            // ...pertenece a una conexión...
            $facturaQuery->whereHas('conexion', function ($conexionQuery) use ($afiliadoId) {
                // ...que pertenece a ESTE afiliado.
                $conexionQuery->where('afiliado_id', $afiliadoId);
            });
        });

        // (Aquí puedes añadir filtros de fecha si quieres)
        
        // ¡ASEGÚRATE DE CREAR ESTA VISTA! resources/js/Pages/Usuario/HistorialPagos.vue
        return Inertia::render('Usuario/HistorialPagos', [ 
            'pagos' => $query->orderBy('fecha_pago', 'desc')
                             ->paginate(15)
                             ->withQueryString(),
            'filters' => $request->only([]), // Añade filtros si los pones
            'error' => null
        ]);
    }

    // --- MÉTODOS OBSOLETOS (NO USAR) ---
    public function edit($id) { abort(405, 'Los pagos no se editan, se anulan.'); }
    public function update(Request $request, $id) { abort(405, 'Los pagos no se editan, se anulan.'); }
    public function destroy($id) { abort(405, 'Los pagos no se eliminan, se anulan.'); }
}