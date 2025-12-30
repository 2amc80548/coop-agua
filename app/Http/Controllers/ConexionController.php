<?php

namespace App\Http\Controllers;

use App\Models\Conexion;
use App\Models\Afiliado;
use App\Models\Zona; 
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule; 
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\Log; 

class ConexionController extends Controller
{
    /**
     * Muestra la lista paginada y filtrable de conexiones.
     */
    public function index(Request $request)
    {
        // Cargar 'afiliado'
        $query = Conexion::with(['afiliado:id,nombre_completo,ci', 'zona:id,nombre']); 
       // 1. Buscador (Código Medidor, CI o Nombre del Afiliado)
        $query->when($request->input('search'), function ($q, $search) {
            $q->where(function ($w) use ($search) {

                $w->where('codigo_medidor', 'like', "%{$search}%")
                ->orWhereHas('afiliado', function ($afiliadoQuery) use ($search) {
                    $afiliadoQuery->where('ci', 'like', "%{$search}%")
                                    ->orWhere('nombre_completo', 'like', "%{$search}%");
                });

            });
        });


        
        $query->when($request->input('zona_id'), function ($q, $zonaId) {
            $q->where('zona_id', $zonaId);
        });

        // 2. Filtro por Estado
        $query->when($request->input('estado'), function ($q, $estado) {
            $q->where('estado', $estado);
        });

        // 3. Filtro por Tipo de Conexión
        $query->when($request->input('tipo_conexion'), function ($q, $tipo) {
            $q->where('tipo_conexion', $tipo);
        });



        return Inertia::render('Conexiones/Index', [
            'conexiones' => $query->orderBy('codigo_medidor')
                                 ->paginate(10)
                                 ->withQueryString(),
            'filters' => $request->only(['search', 'estado', 'tipo_conexion', 'zona_id']), 
            'zonas' => Zona::orderBy('nombre')->get(['id', 'nombre']), 
        ]);
    }

    /**
     * Muestra el formulario de creación.
     */
    public function create()
    {
 
        return Inertia::render('Conexiones/Create', [
            'zonas' => Zona::orderBy('nombre')->get(['id', 'nombre']), 
            'zonaStoreUrl' => route('zonas.store'), 
            'searchAfiliadosUrl' => route('afiliados.buscarPorCI', ['ci' => '__CI_PLACEHOLDER__']),
        ]);
    }

    /**
     * Muestra el detalle de una conexión.
     */
    public function show($id) 
    {
        // Cargar las relaciones 'afiliado'
        $conexion = Conexion::with([
                            'afiliado', 
                            'zona', 
                            'lecturas' => fn($q) => $q->orderBy('periodo', 'desc')->limit(12)
                        ])->findOrFail($id); 

        return Inertia::render('Conexiones/Show', [
            'conexion' => $conexion, 
        ]);
    }


    /**
     * Muestra el formulario para editar una conexión.
     */
    public function edit($id) 
    {
        $conexion = Conexion::with('afiliado')->findOrFail($id); 

        return Inertia::render('Conexiones/Edit', [
            'conexion'  => $conexion, 
            'zonas' => Zona::orderBy('nombre')->get(['id', 'nombre']), 
            'zonaStoreUrl' => route('zonas.store'), 
            'searchAfiliadosUrl' => route('afiliados.buscarPorCI', ['ci' => '__CI_PLACEHOLDER__']), 
        ]);
    }

    /**
     * Guarda una nueva conexión.
     */
    public function store(Request $request)
{
    // 1. Validar estrictamente lo que llega de Vue
    $validated = $request->validate([
        'codigo_medidor'    => 'required|string|unique:conexiones,codigo_medidor|max:50',
        'afiliado_id'       => 'required|exists:afiliados,id',
        'estado'            => 'required|in:activo,suspendido,eliminado',
        'direccion'         => 'required|string|max:255',
        'zona_id'           => 'required|exists:zonas,id', 
        'fecha_instalacion' => 'required|date',
        'tipo_conexion'     => 'required|in:domiciliaria,comercial,institucional,otro',
        // Estos son los campos nuevos del checkbox
        'es_antiguo'        => 'nullable|boolean',
        'lectura_anterior'  => 'nullable|numeric|min:0',
    ]);

    try {
        return DB::transaction(function () use ($request, $validated) {
            
            // 2. Crear la conexión
            $conexion = \App\Models\Conexion::create($validated);

            // 3. Lógica de los 1100 (Lectura Semilla)
            $montoArranque = $request->es_antiguo ? $request->lectura_anterior : 0;

            // IMPORTANTE: Ponemos periodo del mes pasado para NO bloquear el mes actual
            $periodoBase = date('Y-m', strtotime("-1 month"));

            \App\Models\Lectura::create([
                'conexion_id'      => $conexion->id,
                'fecha_lectura'    => $validated['fecha_instalacion'],
                'periodo'          => $periodoBase, 
                'lectura_anterior' => $montoArranque,
                'lectura_actual'   => $montoArranque, // Iguales para que consumo sea 0
                'observacion'      => 'Arranque de sistema',
                'registrado_por'   => Auth::id(),
                'estado'           => 'finalizado', // <--- IMPORTANTE: No aparecerá para facturar
            ]);

            // 4. Activar afiliado
            $afiliado = \App\Models\Afiliado::find($validated['afiliado_id']);
            if ($afiliado && $afiliado->estado_servicio === 'Pendiente') {
                $afiliado->update(['estado_servicio' => 'activo']);
            }

            return redirect()->route('conexiones.index')->with('success', '✅ Conexión creada correctamente.');
        });

    } catch (\Exception $e) {
        // Esto te dirá el error real en el log
        Log::error("Error al guardar conexión: " . $e->getMessage());
        
        // Devolvemos el error a la pantalla para que lo veas
        return redirect()->back()
            ->withInput()
            ->withErrors(['error_general' => 'Fallo en la base de datos: ' . $e->getMessage()]);
    }
}

    /**
     * Actualiza una conexión.
     */
    public function update(Request $request, $id) 
    {
        $conexion = Conexion::findOrFail($id); 

        $validated = $request->validate([
            'codigo_medidor'  => ['required','string','max:50', Rule::unique('conexiones')->ignore($conexion->id)],
            'afiliado_id'     => 'required|exists:afiliados,id', 
            'estado'          => 'required|in:activo,suspendido,eliminado',
            'direccion'       => 'required|string|max:255',
            'zona_id'         => 'required|exists:zonas,id', 
            'fecha_instalacion' => 'required|date',
            'tipo_conexion'   => 'required|in:domiciliaria,comercial,institucional,otro',
        ]);

        try {
            $conexion->update($validated); 
        } catch (\Exception $e) {
             Log::error('Error al actualizar conexión:', ['error' => $e->getMessage(), 'request' => $request->all()]);
            return redirect()->back()->withInput()->withErrors(['error_general' => 'Error al actualizar la conexión.']);
        }
        
        return redirect()->route('conexiones.index')->with('success', '✅ Conexión actualizada correctamente.');
    }
    
    /**
     * Elimina una conexión (Solo Admin).
     */
    public function destroy($id)
    {
        $conexion = Conexion::findOrFail($id); 

        // Regla: No borrar si tiene lecturas o facturas
        if ($conexion->lecturas()->exists() || $conexion->facturas()->exists()) {
             return redirect()->back()->withErrors(['error_general' => 'No se puede eliminar. La conexión tiene lecturas o facturas asociadas.']);
        }
        
        $conexion->delete();
        return redirect()->back()->with('success', '🗑️ Conexión eliminada correctamente.');
    }
}