<?php

namespace App\Http\Controllers;

// --- Imports (TODOS SON NECESARIOS) ---
use App\Models\Lectura;
use App\Models\Conexion;
use App\Models\Tarifa;
use App\Models\Zona; // ¡Importante!
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule; // Para validación

class LecturaController extends Controller
{
    /**
     * Define los permisos para este controlador.
     */
    public function __construct()
    {
        $this->middleware('role:Administrador|Secretaria|Tecnico');
        $this->middleware('role:Administrador|Tecnico')->except(['index', 'show']); // Secretaria solo puede ver
        $this->middleware('role:Administrador')->only('destroy'); // Solo Admin puede borrar
        $this->middleware('role:Administrador|Secretaria|Tecnico')->only([ // APIs
            'apiSearchConexiones', 
            'apiGetPendientes', 
            'apiGetTarifaActiva', 
            'showAviso'
        ]);
    }

    /**
     * Muestra lista paginada y filtrable de lecturas.
     */
    public function index(Request $request)
    {
        $query = Lectura::with([
            // Corregido a 'afiliado' y 'zona'
            'conexion.afiliado:id,nombre_completo,ci', 
            'conexion.zona:id,nombre', // Cargar la zona de la conexión
            'usuarioRegistrado:id,name'
        ]);

        // Búsqueda
        $query->when($request->input('search'), function ($q, $search) {
            $q->whereHas('conexion', function ($conexionQuery) use ($search) {
                $conexionQuery->where('codigo_medidor', 'like', "%{$search}%")
                    ->orWhereHas('afiliado', function ($afiliadoQuery) use ($search) { // Corregido a 'afiliado'
                        $afiliadoQuery->where('ci', 'like', "%{$search}%")
                                      ->orWhere('nombre_completo', 'like', "%{$search}%");
                    });
            });
        });
        
        // Filtro por Período
        $query->when($request->input('periodo'), function ($q, $periodo) {
            if (preg_match('/^\d{4}-\d{2}$/', $periodo)) {
                $q->where('periodo', $periodo);
            }
        });

        // Filtro por Estado
        $query->when($request->input('estado'), function ($q, $estado) {
            $q->where('estado', $estado);
        });
        
        // ¡NUEVO! Filtro por Zona
        $query->when($request->input('zona_id'), function ($q, $zonaId) {
             $q->whereHas('conexion', function ($conQuery) use ($zonaId) {
                 $conQuery->where('zona_id', $zonaId);
             });
        });

        $periodos = Lectura::select('periodo')
                            ->whereNotNull('periodo')->where('periodo', '!=', '')
                            ->distinct()->orderBy('periodo', 'desc')->pluck('periodo');

        return Inertia::render('Lecturas/Index', [
            'lecturas' => $query->orderBy('fecha_lectura', 'desc')
                                ->orderBy('id', 'desc')
                                ->paginate(15) // ¡PAGINADO!
                                ->withQueryString(),
            'filters' => $request->only(['search', 'periodo', 'estado', 'zona_id']), // Añadido 'zona_id'
            'periodos' => $periodos,
            'zonas' => Zona::orderBy('nombre')->get(['id', 'nombre']), // <-- ¡AÑADIDO! Enviar zonas al Index
        ]);
    }

    /**
     * Muestra el formulario para crear una nueva lectura.
     * (¡CORREGIDO!)
     */
    public function create()
    {
        $periodoActual = Carbon::now()->format('Y-m');
        $ultimoPeriodoRegistrado = Lectura::max('periodo') ?? $periodoActual;
        
        // ¡CORREGIDO! Solo obtenemos las zonas de la tabla 'zonas'
        $zonas = Zona::orderBy('nombre')->get(['id', 'nombre']);

        return Inertia::render('Lecturas/Create', [
            'apiUrlSearchConexiones' => route('api.conexiones.search_with_reading'),
            'apiUrlPendientes' => route('api.lecturas.pendientes'),
            'apiUrlTarifaActiva' => route('api.tarifas.activa'),
            'periodoActual' => $periodoActual,
            'ultimoPeriodoRegistrado' => $ultimoPeriodoRegistrado,
            'zonas' => $zonas, // Pasamos las zonas para el filtro de pendientes
        ]);
    }

    /**
     * Guarda una nueva lectura en la base de datos.
     * (Tu lógica de 'store' ya era profesional y está correcta)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'conexion_id' => 'required|exists:conexiones,id',
            'fecha_lectura' => 'required|date|before_or_equal:today',
            'lectura_actual' => 'required|numeric|min:0',
            'observacion' => 'nullable|string|max:255',
        ]);
        
        try {
            $lectura = DB::transaction(function () use ($validated) {
                $conexionId = $validated['conexion_id'];
                $fechaLectura = Carbon::parse($validated['fecha_lectura']);
                $periodo = $fechaLectura->format('Y-m');

                $ultimaLectura = Lectura::where('conexion_id', $conexionId)
                                        ->orderBy('fecha_lectura', 'desc')
                                        ->orderBy('id', 'desc')
                                        ->first();
                $lecturaAnterior = $ultimaLectura ? $ultimaLectura->lectura_actual : 0;

                if ($validated['lectura_actual'] < $lecturaAnterior) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'lectura_actual' => "La lectura actual ({$validated['lectura_actual']}) no puede ser menor que la anterior ({$lecturaAnterior})."
                    ]);
                }
                
                $existeLecturaPeriodo = Lectura::where('conexion_id', $conexionId)
                                             ->where('periodo', $periodo)
                                             ->exists();
                if ($existeLecturaPeriodo) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'conexion_id' => 'Ya existe una lectura registrada para esta conexión en el período ' . $periodo . '.'
                    ]);
                }
                 
                return Lectura::create([
                   'conexion_id' => $conexionId,
                   'fecha_lectura' => $fechaLectura->toDateString(),
                   'periodo' => $periodo,
                   'lectura_anterior' => $lecturaAnterior,
                   'lectura_actual' => $validated['lectura_actual'],
                   'observacion' => $validated['observacion'] ?? null,
                   'registrado_por' => Auth::id(),
                   'estado' => 'pendiente',
               ]);
            });

            return redirect()->route('lecturas.index')
                           ->with('success', '✅ Lectura registrada. Puede imprimir el aviso.')
                           ->with('lectura_id_reciente', $lectura->id);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
             Log::error('Error al guardar lectura:', ['error' => $e->getMessage(), 'request' => $request->all()]);
            return redirect()->back()
                           ->withErrors(['error_general' => 'Ocurrió un error inesperado al guardar. Revise los logs.'])
                           ->withInput();
        }
    }

    /**
     * Muestra los detalles de una lectura específica.
     */
     public function show($id)
     {
         $lectura = Lectura::with([
                            'conexion.afiliado', // ¡Corregido!
                            'conexion.zona',     // ¡Añadido!
                            'usuarioRegistrado:id,name',
                            'factura:id,lectura_id,estado,monto_total' 
                        ])->findOrFail($id);
 
         return Inertia::render('Lecturas/Show', [
             'lectura' => $lectura,
         ]);
     }

    /**
     * Muestra el formulario para editar una lectura (si está pendiente).
     */
    public function edit($id)
    {
         $lectura = Lectura::with([
                         'conexion.afiliado', // ¡Corregido!
                         'conexion.zona'      // ¡Añadido!
                     ])->findOrFail($id);

         if ($lectura->estado !== 'pendiente') {
             return redirect()->route('lecturas.show', $lectura->id)
                            ->withErrors(['error_general' => 'No se puede editar una lectura que ya ha sido facturada.']);
         }
    
         return Inertia::render('Lecturas/Edit', [
             'lectura' => $lectura, 
             // No necesitamos pasar 'conexiones' ni 'afiliados'
         ]);
    }

    /**
     * Actualiza una lectura existente (si está pendiente).
     */
    public function update(Request $request, $id)
    {
        $lectura = Lectura::findOrFail($id);

        if ($lectura->estado !== 'pendiente') {
            return redirect()->back()
                           ->withErrors(['error_general' => 'No se puede actualizar una lectura que ya ha sido facturada.'])
                           ->withInput();
        }

        $validated = $request->validate([
            'fecha_lectura' => 'required|date|before_or_equal:today',
            'lectura_actual' => 'required|numeric|min:0|gte:' . $lectura->lectura_anterior, 
            'observacion' => 'nullable|string|max:255',
        ], [
            'lectura_actual.gte' => 'La lectura actual no puede ser menor que la anterior (' . $lectura->lectura_anterior . ').'
        ]);
        
        $nuevoPeriodo = Carbon::parse($validated['fecha_lectura'])->format('Y-m');
        
        if ($nuevoPeriodo !== $lectura->periodo) {
             $existeLecturaPeriodo = Lectura::where('conexion_id', $lectura->conexion_id)
                                          ->where('periodo', $nuevoPeriodo)
                                          ->where('id', '!=', $lectura->id) 
                                          ->exists();
             if ($existeLecturaPeriodo) {
                 throw \Illuminate\Validation\ValidationException::withMessages([
                     'fecha_lectura' => 'Ya existe otra lectura para esta conexión en el período ' . $nuevoPeriodo . '.'
                 ]);
             }
        }

        $lectura->update([
             'fecha_lectura' => $validated['fecha_lectura'],
             'periodo' => $nuevoPeriodo,
             'lectura_actual' => $validated['lectura_actual'],
             'observacion' => $validated['observacion'],
        ]);

        return redirect()->route('lecturas.index')
                       ->with('success', '✅ Lectura actualizada correctamente.');
    }

    /**
     * Elimina una lectura (si está pendiente). (Solo Admin)
     */
    public function destroy($id)
    {
        $lectura = Lectura::findOrFail($id);
        if ($lectura->estado !== 'pendiente') {
             return redirect()->back()
                           ->withErrors(['error_general' => 'No se puede eliminar una lectura que ya ha sido facturada.']);
        }
        $lectura->delete();
        return redirect()->route('lecturas.index')->with('success', '🗑️ Lectura eliminada.');
    }


    // --- APIs --- 

    /**
     * API: Busca conexiones (¡Corregido para 'afiliado' y 'zona_nombre')
     */
    public function apiSearchConexiones(Request $request) // (Tu apiSearchConexiones se ve bien, solo cambia 'beneficiario' a 'afiliado')
    {
        $term = $request->input('term', '');
        if (strlen($term) < 2) return response()->json([]);

        $conexiones = Conexion::with([
                'afiliado:id,nombre_completo,ci,adulto_mayor', 
                'zona:id,nombre' // ¡Añadido!
            ]) 
            ->where(function ($query) use ($term) {
                $query->where('codigo_medidor', 'like', "%{$term}%")
                      ->orWhereHas('afiliado', function ($q) use ($term) { // ¡'afiliado'!
                          $q->where('nombre_completo', 'like', "%{$term}%")
                            ->orWhere('ci', 'like', "%{$term}%");
                      });
            })
            ->select('id', 'codigo_medidor', 'afiliado_id', 'direccion', 'zona_id') // ¡'zona_id'!
            ->limit(10)
            ->get();

        $results = $conexiones->map(function ($conexion) {
             $ultimaLectura = Lectura::where('conexion_id', $conexion->id)
                                     ->orderBy('fecha_lectura', 'desc')
                                     ->orderBy('id', 'desc')
                                     ->value('lectura_actual'); 

             return [
                 'id' => $conexion->id,
                 'codigo_medidor' => $conexion->codigo_medidor,
                 'direccion' => $conexion->direccion,
                 'zona_nombre' => $conexion->zona?->nombre, // ¡Añadido!
                 'afiliado_nombre' => $conexion->afiliado?->nombre_completo ?? 'N/A', // ¡'afiliado'!
                 'afiliado_ci' => $conexion->afiliado?->ci ?? 'N/A',
                 'afiliado_adulto_mayor' => $conexion->afiliado?->adulto_mayor ?? false, 
                 'lectura_anterior' => $ultimaLectura ?? 0, 
             ];
        });

        return response()->json($results);
    }

    /**
     * API: Devuelve conexiones pendientes (¡Corregido para 'zona_id')
     */
    public function apiGetPendientes(Request $request)
    {
        $periodo = $request->input('periodo', Carbon::now()->format('Y-m'));
        $zonaId = $request->input('zona_id'); // <-- ¡Cambiado a zona_id!

        if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            return response()->json(['error' => 'Formato de período inválido. Use YYYY-MM.'], 400);
        }

        try {
            $conexionesConLectura = DB::table('lecturas')
                                    ->where('periodo', $periodo)
                                    ->pluck('conexion_id'); 

            $query = Conexion::with([
                'afiliado:id,nombre_completo,ci,adulto_mayor',
                'zona:id,nombre' // ¡Añadido!
            ])
                ->where('estado', 'activo') 
                ->whereNotIn('id', $conexionesConLectura);

            // --- ¡CORRECCIÓN! Filtrar por la nueva columna 'zona_id'
            $query->when($zonaId, function ($q, $zonaId) {
                $q->where('zona_id', $zonaId);
            });
            // --- FIN CORRECCIÓN ---

            $pendientes = $query->select('id', 'codigo_medidor', 'afiliado_id', 'direccion', 'zona_id')
                                ->orderBy('zona_id')->orderBy('direccion')->get();
                            
            $results = $pendientes->map(function ($conexion) {
                 $ultimaLectura = Lectura::where('conexion_id', $conexion->id)
                                         ->orderBy('fecha_lectura', 'desc')->orderBy('id', 'desc')
                                         ->value('lectura_actual');
                 return [
                     'id' => $conexion->id,
                     'codigo_medidor' => $conexion->codigo_medidor,
                     'direccion' => $conexion->direccion,
                     'zona_nombre' => $conexion->zona?->nombre, // ¡Añadido!
                     'afiliado_nombre' => $conexion->afiliado?->nombre_completo ?? 'N/A',
                     'afiliado_ci' => $conexion->afiliado?->ci ?? 'N/A',
                     'afiliado_adulto_mayor' => $conexion->afiliado?->adulto_mayor ?? false,
                     'lectura_anterior' => $ultimaLectura ?? 0,
                 ];
            });

            return response()->json($results);
            
        } catch (\Exception $e) {
            Log::error('Error en apiGetPendientes:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error de servidor al buscar pendientes.'], 500);
        }
    }

    /**
     * API: Devuelve los detalles de la tarifa activa.
     * (Tu código original está perfecto)
     */
    public function apiGetTarifaActiva()
    {
        try {
            $tarifa = Tarifa::where('activo', 1)->orderBy('vigente_desde', 'desc')->first();
            if (!$tarifa) {
                Log::error("¡CRÍTICO! No se encontró ninguna tarifa activa en la base de datos.");
                return response()->json(['error' => 'No hay tarifa activa configurada.'], 500);
            }
            return response()->json([
                'min_m3' => (float) $tarifa->min_m3,
                'min_monto' => (float) $tarifa->min_monto,
                'precio_m3' => (float) $tarifa->precio_m3,
                'descuento_adulto_mayor_pct' => (float) $tarifa->descuento_adulto_mayor_pct,
            ]);
        } catch (\Exception $e) {
            Log::error('Error en apiGetTarifaActiva:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error de servidor al buscar tarifa.'], 500);
        }
    }
    
    /**
     * Muestra la vista simple para el Aviso de Cobranza (para imprimir).
     * (Tu código original está perfecto, solo aseguramos 'afiliado')
     */
     public function showAviso($id) 
     {
         $lectura = Lectura::with([
                         'conexion.afiliado', // ¡Corregido!
                         'usuarioRegistrado:id,name'
                     ])->findOrFail($id);
         
         $tarifa = Tarifa::where('activo', 1)->orderBy('vigente_desde', 'desc')->first();
         if (!$tarifa) { abort(500, 'No hay tarifa activa.'); }

         $consumo = $lectura->lectura_actual - $lectura->lectura_anterior;
         $montoCalculado = 0;
         
         // REGLA DE CÁLCULO
         if ($consumo <= $tarifa->min_m3 && $tarifa->min_monto > ($consumo * $tarifa->precio_m3) ) {
             $montoCalculado = $tarifa->min_monto;
         } else {
             $montoCalculado = $consumo * $tarifa->precio_m3;
         }
         
         $descuento = 0;
         if ($lectura->conexion->afiliado?->adulto_mayor && $tarifa->descuento_adulto_mayor_pct > 0) {
             $descuento = ($montoCalculado * $tarifa->descuento_adulto_mayor_pct) / 100;
         }
         $montoEstimado = $montoCalculado - $descuento;

         return View::make('avisos.cobranza', [ 
             'lectura' => $lectura, 'tarifa' => $tarifa, 'consumo' => $consumo,
             'montoEstimado' => $montoEstimado, 'descuentoAplicado' => $descuento,
             'fechaImpresion' => Carbon::now('America/La_Paz')->format('d/m/Y H:i')
         ]); 
     }

} // Fin de la clase