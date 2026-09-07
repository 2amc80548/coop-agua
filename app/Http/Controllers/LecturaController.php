<?php

namespace App\Http\Controllers;

use App\Models\Lectura;
use App\Models\Conexion;
use App\Models\Zona;
use App\Models\Tarifa;
use App\Services\TarifaCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;

class LecturaController extends Controller
{
    /**
     * Inyectamos el servicio de cálculo de tarifas.
     * Eliminamos la dependencia directa de la clase Tarifa en este controlador.
     */
    public function __construct(
        protected TarifaCalculatorService $tarifaService
    ) {}
    /**
     * Muestra lista paginada y filtrable de lecturas.
     */
    public function index(Request $request)
    {
        $query = Lectura::with([
            'conexion.afiliado:id,nombre_completo,ci', 
            'conexion.zona:id,nombre',
            'usuarioRegistrado:id,name'
        ]);

        // Búsqueda
        $query->when($request->input('search'), function ($q, $search) {
            $q->whereHas('conexion', function ($conexionQuery) use ($search) {
                $conexionQuery->where('codigo_medidor', 'like', "%{$search}%")
                    ->orWhereHas('afiliado', function ($afiliadoQuery) use ($search) { 
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
        
        // Filtro por Zona
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
                                ->paginate(10) // ¡PAGINADO!
                                ->withQueryString(),
            'filters' => $request->only(['search', 'periodo', 'estado', 'zona_id']), 
            'periodos' => $periodos,
            'zonas' => Zona::orderBy('nombre')->get(['id', 'nombre']), 
        ]);
    }

    /**
     * Muestra el formulario para crear una nueva lectura.
     */
    public function create()
    {
        $periodoActual = Carbon::now()->format('Y-m');
        $ultimoPeriodoRegistrado = Lectura::max('periodo') ?? $periodoActual;
        
        // Solo obtenemos las zonas de la tabla 'zonas'
        $zonas = Zona::orderBy('nombre')->get(['id', 'nombre']);

        return Inertia::render('Lecturas/Create', [
            'apiUrlSearchConexiones' => route('api.conexiones.search_with_reading'),
            'apiUrlPendientes' => route('api.lecturas.pendientes'),
            'apiUrlTarifaActiva' => route('api.tarifas.activa'),
            'periodoActual' => $periodoActual,
            'ultimoPeriodoRegistrado' => $ultimoPeriodoRegistrado,
            'zonas' => $zonas, 
        ]);
    }

    /**
     * Guarda una nueva lectura en la base de datos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'conexion_id' => 'required|exists:conexiones,id',
            'fecha_lectura' => 'required|date',
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
                            'conexion.afiliado', 
                            'conexion.zona', 
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
                         'conexion.afiliado', 
                         'conexion.zona'     
                     ])->findOrFail($id);

         if ($lectura->estado !== 'pendiente') {
             return redirect()->route('lecturas.show', $lectura->id)
                            ->withErrors(['error_general' => 'No se puede editar una lectura que ya ha sido facturada.']);
         }
    
         return Inertia::render('Lecturas/Edit', [
             'lectura' => $lectura, 
            
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
            'fecha_lectura' => 'required|date',
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
     * API: Busca conexiones por medidor, nombre o CI.
     *
     * OPTIMIZACIÓN N+1 (Hallazgo 2.1):
     * La última lectura se resuelve con una subconsulta SQL en addSelect(),
     * eliminando las N consultas individuales que existían dentro del map().
     * Para 10 resultados: antes = 11 queries, ahora = 1 query.
     */
    public function apiSearchConexiones(Request $request)
    {
        $term = $request->input('term', '');
        if (strlen($term) < 2) {
            return response()->json([]);
        }

        // Subconsulta: obtiene la lectura_actual más reciente de cada conexión
        $ultimaLecturaSubquery = \App\Models\Lectura::select('lectura_actual')
            ->whereColumn('conexion_id', 'conexiones.id')
            ->orderBy('fecha_lectura', 'desc')
            ->orderBy('id', 'desc')
            ->limit(1);

        $conexiones = Conexion::with([
            'afiliado:id,nombre_completo,ci,adulto_mayor',
            'zona:id,nombre',
        ])
            ->select('id', 'codigo_medidor', 'afiliado_id', 'direccion', 'zona_id', 'tipo_conexion')
            ->addSelect(['lectura_anterior' => $ultimaLecturaSubquery]) // 1 query total
            ->where(function ($query) use ($term) {
                $query->where('codigo_medidor', 'like', "%{$term}%")
                    ->orWhereHas('afiliado', function ($q) use ($term) {
                        $q->where('nombre_completo', 'like', "%{$term}%")
                          ->orWhere('ci', 'like', "%{$term}%");
                    });
            })
            ->limit(10)
            ->get();

        $results = $conexiones->map(fn($conexion) => [
            'id'                    => $conexion->id,
            'codigo_medidor'        => $conexion->codigo_medidor,
            'direccion'             => $conexion->direccion,
            'zona_nombre'           => $conexion->zona?->nombre,
            'afiliado_nombre'       => $conexion->afiliado?->nombre_completo ?? 'N/A',
            'afiliado_ci'           => $conexion->afiliado?->ci ?? 'N/A',
            'afiliado_adulto_mayor' => (bool) ($conexion->afiliado?->adulto_mayor ?? false),
            'lectura_anterior'      => (float) ($conexion->lectura_anterior ?? 0),
            'tipo_conexion'         => $conexion->tipo_conexion,
        ]);

        return response()->json($results);
    }

    /**
     * API: Devuelve conexiones pendientes de lectura para un período.
     *
     * OPTIMIZACIÓN N+1 (Hallazgo 2.1):
     * La última lectura de cada conexión se resuelve con addSelect + subconsulta,
     * en lugar de N consultas individuales dentro del map().
     * Para 500 conexiones: antes = 501 queries, ahora = 3 queries.
     */
    public function apiGetPendientes(Request $request)
    {
        $periodo = $request->input('periodo', Carbon::now()->format('Y-m'));
        $zonaId  = $request->input('zona_id');

        if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            return response()->json(['error' => 'Formato de período inválido. Use YYYY-MM.'], 400);
        }

        try {
            // IDs de conexiones que ya tienen lectura en este período
            $conexionesConLectura = DB::table('lecturas')
                ->where('periodo', $periodo)
                ->pluck('conexion_id');

            // Subconsulta: última lectura_actual de cada conexión (sin N+1)
            $ultimaLecturaSubquery = \App\Models\Lectura::select('lectura_actual')
                ->whereColumn('conexion_id', 'conexiones.id')
                ->orderBy('fecha_lectura', 'desc')
                ->orderBy('id', 'desc')
                ->limit(1);

            $pendientes = Conexion::with([
                'afiliado:id,nombre_completo,ci,adulto_mayor',
                'zona:id,nombre',
            ])
                ->select('id', 'codigo_medidor', 'afiliado_id', 'direccion', 'zona_id', 'tipo_conexion')
                ->addSelect(['lectura_anterior' => $ultimaLecturaSubquery]) // 1 subconsulta SQL
                ->where('estado', 'activo')
                ->whereNotIn('id', $conexionesConLectura)
                ->when($zonaId, fn($q) => $q->where('zona_id', $zonaId))
                ->orderBy('zona_id')
                ->orderBy('direccion')
                ->get();

            $results = $pendientes->map(fn($conexion) => [
                'id'                    => $conexion->id,
                'codigo_medidor'        => $conexion->codigo_medidor,
                'direccion'             => $conexion->direccion,
                'zona_nombre'           => $conexion->zona?->nombre,
                'afiliado_nombre'       => $conexion->afiliado?->nombre_completo ?? 'N/A',
                'afiliado_ci'           => $conexion->afiliado?->ci ?? 'N/A',
                'afiliado_adulto_mayor' => (bool) ($conexion->afiliado?->adulto_mayor ?? false),
                'lectura_anterior'      => (float) ($conexion->lectura_anterior ?? 0),
                'tipo_conexion'         => $conexion->tipo_conexion,
            ]);

            return response()->json($results);

        } catch (\Exception $e) {
            Log::error('LecturaController@apiGetPendientes:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error de servidor al buscar conexiones pendientes.'], 500);
        }
    }

    /**
     * API: Devuelve los detalles de la tarifa activa.
     */
    public function apiGetTarifaActiva(Request $request)
    {
        $tipo = trim($request->input('tipo_conexion', ''));

        if (!$tipo) {
            return response()->json(['error' => 'tipo_conexion es requerido'], 400);
        }

        try {
            $tipoLower = mb_strtolower($tipo);
            $tarifa = Tarifa::where('activo', true)
                            ->where(DB::raw('LOWER(tipo_conexion)'), $tipoLower)
                            ->orderBy('vigente_desde', 'desc')
                            ->first();

            if (!$tarifa) {
                // Fallback: si no hay tarifa por tipo específico, buscar la tarifa activa más reciente
                $tarifa = Tarifa::where('activo', true)
                                ->orderBy('vigente_desde', 'desc')
                                ->first();
            }

            if (!$tarifa) {
                Log::error("No se encontró tarifa activa para el tipo: {$tipo}");
                return response()->json(['error' => 'No hay tarifa activa configurada.'], 404);
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
     */
    public function showAviso($id)
    {
        $lectura = Lectura::with([
            'conexion.afiliado',
            'conexion',
            'usuarioRegistrado:id,name',
        ])->findOrFail($id);

        // El servicio obtiene la tarifa y lanza excepción si no existe
        $tipo   = $lectura->conexion->tipo_conexion;
        $tarifa = $this->tarifaService->getTarifaActiva($tipo);

        if (!$tarifa) {
            abort(500, "No hay tarifa activa para el tipo de conexión: '{$tipo}'. Configure una tarifa antes de imprimir avisos.");
        }

        // El servicio centraliza el cálculo (consumo, cobro mínimo, descuento adulto mayor)
        $calculo = $this->tarifaService->calcular($lectura, $tarifa);

        return View::make('avisos.cobranza', [
            'lectura'           => $lectura,
            'tarifa'            => $tarifa,
            'consumo'           => $calculo['consumo'],
            'montoEstimado'     => $calculo['total'],
            'descuentoAplicado' => $calculo['descuento'],
            'fechaImpresion'    => Carbon::now('America/La_Paz')->format('d/m/Y H:i'),
        ]);
    }


} 