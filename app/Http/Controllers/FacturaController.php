<?php

namespace App\Http\Controllers;

// --- Imports ---
use App\Models\Factura;
use App\Models\Pago;
use App\Models\Conexion; 
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Facades\View;
use App\Models\Tarifa;
; 

class FacturaController extends Controller
{


    /**
     * 1. Muestra la lista  de facturas.
     * Con filtros, paginación y búsqueda.
     */
    public function index(Request $request)
    {
        $query = Factura::with([
            'conexion:id,codigo_medidor,afiliado_id',
            'conexion.afiliado:id,nombre_completo,ci' 
        ]);

        // --- Filtros ---
        // 1. Búsqueda por N° Factura, Cód. Medidor, CI o Nombre
        $query->when($request->input('search'), function ($q, $search) {
            $q->where('id', 'like', "%{$search}%")
              ->orWhereHas('conexion', function ($conQuery) use ($search) {
                  $conQuery->where('codigo_medidor', 'like', "%{$search}%")
                           ->orWhereHas('afiliado', function ($afilQuery) use ($search) { 
                               $afilQuery->where('ci', 'like', "%{$search}%")
                                         ->orWhere('nombre_completo', 'like', "%{$search}%");
                           });
              });
        });

        // 2. Filtro por Período
        $query->when($request->input('periodo'), function ($q, $periodo) {
            if (preg_match('/^\d{4}-\d{2}$/', $periodo)) {
                $q->where('periodo', $periodo);
            }
        });

        // 3. Filtro por Estado (¡'impaga' por defecto!)
        $estadoFiltro = $request->input('estado', 'impaga'); // 'impaga' es el defecto
        if ($estadoFiltro !== 'todos') { // 'todos' es la opción para verlas todas
             $query->where('estado', $estadoFiltro);
        }

        // 4. Filtro por Rango de Fechas (opcional)
        $query->when($request->filled('fecha_inicio') && $request->filled('fecha_fin'), function ($q) use ($request) {
             try {
                $inicio = Carbon::parse($request->fecha_inicio)->startOfDay();
                $fin = Carbon::parse($request->fecha_fin)->endOfDay();
                $q->whereBetween('fecha_emision', [$inicio, $fin]);
             } catch (\Exception $e) { /* Ignorar fechas inválidas */ }
        });

        // Obtener períodos únicos para el dropdown
        $periodos = Factura::select('periodo')
                            ->whereNotNull('periodo')->where('periodo', '!=', '')
                            ->distinct()->orderBy('periodo', 'desc')->pluck('periodo');

        return Inertia::render('Facturas/Index', [
            'facturas' => $query->orderBy('fecha_emision', 'desc')
                                ->orderBy('id', 'desc')
                                ->paginate(10) //  PAGINACIÓN
                                ->withQueryString(), 
            'filters' => [ 
                'search' => $request->input('search'),
                'periodo' => $request->input('periodo'),
                'estado' => $estadoFiltro,
                'fecha_inicio' => $request->input('fecha_inicio'),
                'fecha_fin' => $request->input('fecha_fin'),
            ],
            'periodos' => $periodos,
        ]);
    }


    /**
     * 2. Muestra el detalle de UNA factura (para verla, pagarla o imprimirla).
     */
    public function show($id)
    {
        $factura = Factura::with([
            'conexion.afiliado', 
            'lectura',          
            'pagos' => fn($q) => $q->orderBy('fecha_pago', 'desc')
        ])->findOrFail($id);

        // --- ¡SEGURIDAD! ---
        // Si el usuario logueado es 'Usuario', verificar que sea SU factura
        $user = Auth::user();
        if ($user->hasRole('Usuario') && $factura->conexion->afiliado_id !== $user->afiliado_id) {
            abort(403, 'No tiene permiso para ver esta factura.');
        }

        // Calcular saldo
        $totalPagado = $factura->pagos->sum('monto_pagado');
        $saldo = $factura->deuda_pendiente ?? max(0, $factura->monto_total - $totalPagado); 

        return Inertia::render('Facturas/Show', [ 
            'factura' => $factura,
            'totalPagado' => $totalPagado,
            'saldoPendiente' => max(0, $saldo),
        ]);
    }

    /**
     * 3. Anula una factura cambia estado, NO la borra.
     */
    public function anular(Request $request, $id)
    {
        $factura = Factura::findOrFail($id);

        if ($factura->estado === 'pagado') {
             return redirect()->back()->withErrors(['error_general' => 'No se puede anular una factura que ya fue pagada.']);
        }
        if ($factura->estado === 'anulada') {
             return redirect()->back()->with('info', 'Esta factura ya se encontraba anulada.');
        }

        try {
            DB::transaction(function () use ($factura) {
                $factura->estado = 'anulada';
                $factura->deuda_pendiente = 0; 
                $factura->save();

                if ($factura->lectura && $factura->lectura->estado === 'facturado') {
                    $factura->lectura->update(['estado' => 'pendiente']);
                }
            });
        } catch (\Exception $e) {
            Log::error('Error al anular factura:', ['error' => $e->getMessage(), 'factura_id' => $id]);
            return redirect()->back()->withErrors(['error_general' => 'Error al anular la factura. Revise los logs.']);
        }

        return redirect()->route('facturas.index')->with('success', 'Factura F-'.str_pad($id, 6, '0', STR_PAD_LEFT).' anulada. La lectura asociada está pendiente de nuevo.');
    }
    
    /**
     * 4. Actualiza el monto de una factura (Descuento Manual).
     */
    public function updateMonto(Request $request, $id)
    {
        $factura = Factura::findOrFail($id);

        if ($factura->estado !== 'impaga') { 
             return redirect()->back()->withErrors(['error_general' => 'Solo se pueden modificar facturas IMPAGAS.']);
        }

        $validated = $request->validate([
            'monto_nuevo' => 'required|numeric|min:0|lte:' . $factura->monto_total, 
            'justificacion_modificacion' => 'required|string|min:10|max:500',
        ], [
            'monto_nuevo.lte' => 'El monto nuevo no puede ser mayor al monto original.'
        ]);
        
        $montoNuevo = $validated['monto_nuevo'];

        DB::transaction(function () use ($factura, $montoNuevo, $validated) {
            $montoAntiguo = $factura->monto_total;
            $factura->monto_total = $montoNuevo;
            $factura->deuda_pendiente = $montoNuevo;
            $factura->justificacion_modificacion = $validated['justificacion_modificacion'] 
                . " (Monto anterior: Bs " . $montoAntiguo . " - Modificado por: " . Auth::user()->name . ")";
            if ($montoNuevo == 0) { $factura->estado = 'pagado'; }
            $factura->save();
        });

        return redirect()->route('facturas.show', $factura->id)
                       ->with('success', '✅ Monto de factura actualizado y justificación guardada.');
    }



    /**
     * 6. Muestra las facturas SOLO para el usuario autenticado (rol Usuario).
     */
   public function misFacturas(Request $request)
    {
        $user = Auth::user();
        if (!$user->afiliado_id) {
            return Inertia::render('Usuario/MisFacturas', [
                'facturas' => ['data' => []], 'filters' => [], 'periodos' => [],
                'error' => 'Tu cuenta de usuario no está asociada a ningún afiliado.'
            ]);
        }
        $afiliadoId = $user->afiliado_id;

        $query = Factura::with('conexion:id,codigo_medidor')
                         ->whereHas('conexion', fn($q) => $q->where('afiliado_id', $afiliadoId));
 
        // Filtro de estado para el usuario ('impaga', 'pagado', 'todos')
        $estadoFiltro = $request->input('estado', 'impaga'); 
        if ($estadoFiltro !== 'todos') {
            $query->where('estado', $estadoFiltro);
        }
         
        $periodos = Factura::whereHas('conexion', fn($q) => $q->where('afiliado_id', $afiliadoId))
                             ->select('periodo')->distinct()->orderBy('periodo','desc')->pluck('periodo');
 
        return Inertia::render('Usuario/MisFacturas', [ 
             'facturas' => $query->orderBy('periodo', 'desc')
                                 ->paginate(10)
                                 ->withQueryString(),
             'filters' => $request->only(['estado']),
             'periodos' => $periodos,
             'error' => null
        ]);
    }

    /**
     * Muestra la factura en HTML/Blade para imprimir 
     */
    public function imprimirFactura($id)
    {
         $user = Auth::user();
         $factura = Factura::with([
                'conexion.afiliado', 
                'lectura.usuarioRegistrado', // Quién leyó 
                'pagos.usuarioRegistrado' // Quién cobró
            ])->findOrFail($id);

         // --- ¡SEGURIDAD! ---
         if ($user->hasRole('Usuario') && $factura->conexion->afiliado_id !== $user->afiliado_id) {
             abort(403, 'No tiene permiso para ver esta factura.');
         }

        $consumo = $factura->lectura
            ? ($factura->lectura->lectura_actual - $factura->lectura->lectura_anterior)
            : $factura->consumo_m3;

        $totalPagado = $factura->pagos->sum('monto_pagado');
        $saldo = $factura->deuda_pendiente ?? max(0, $factura->monto_total - $totalPagado);
        $ultimoPago = $factura->pagos->sortByDesc('fecha_pago')->first();

        // cálculo estimado de subtotal y descuento (solo para mostrar) ---
        $montoBase = null;
        $descuentoEstimado = 0;
        $porcentajeDescuento = 0;

        try {
            $tipoConexion = $factura->conexion->tipo_conexion ?? null;

            if ($factura->lectura && $tipoConexion) {
                $tarifa = Tarifa::where('activo', 1)
                    ->where('tipo_conexion', $tipoConexion)
                    ->orderBy('vigente_desde', 'desc')
                    ->first();

                if ($tarifa) {
                    $calculoDirecto = $consumo * $tarifa->precio_m3;

                    if ($consumo <= $tarifa->min_m3 && $tarifa->min_monto > $calculoDirecto) {
                        $montoBase = $tarifa->min_monto;
                    } else {
                        $montoBase = $calculoDirecto;
                    }

                    $porcentajeDescuento = $tarifa->descuento_adulto_mayor_pct;

                    if ($factura->conexion->afiliado?->adulto_mayor && $tarifa->descuento_adulto_mayor_pct > 0) {
                        $descuentoEstimado = ($montoBase * $tarifa->descuento_adulto_mayor_pct) / 100;
                    }
                }
            }
                    } catch (\Exception $e) {
                        // Si algo falla, no rompemos la vista, solo dejamos los campos en null
                        $montoBase = null;
                        $descuentoEstimado = 0;
                        $porcentajeDescuento = 0;
                    }

                    // Renderizar la vista
                    return View::make('impresiones.factura', [ 
                        'factura' => $factura,
                        'consumo' => $consumo,
                        'totalPagado' => $totalPagado,
                        'saldo' => $saldo,
                        'ultimoPago' => $ultimoPago,
                        'fechaImpresion' => Carbon::now('America/La_Paz')->format('d/m/Y H:i'),
                        // nuevos datos para el detalle
                        'montoBase' => $montoBase,
                        'descuentoEstimado' => $descuentoEstimado,
                        'porcentajeDescuento' => $porcentajeDescuento,
                    ]);

    }

    // // --- Métodos Deshabilitados  ---
    // public function create() { return redirect()->route('facturacion.generar.show')->with('info', 'Las facturas se generan desde "Facturación".'); }
    // public function store(Request $request) { abort(405, 'Acción no permitida. Use el módulo de Facturación.'); }
    // public function edit($id) { abort(405, 'Acción no permitida. Use "Ver" para modificar montos o anular.'); }
    // public function update(Request $request, $id) { abort(405, 'Acción no permitida. Use "updateMonto" o "anular".'); }
    // public function destroy($id) { abort(405, 'Acción no permitida. Use "anular".'); }
}