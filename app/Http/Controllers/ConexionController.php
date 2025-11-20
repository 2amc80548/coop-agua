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
        // Cargar 'afiliado' y la nueva relación 'zona'
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
                                 ->paginate(15)
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
     * (Tu método 'show' original estaba bien, solo añadimos 'zona')
     */
    public function show($id) 
    {
        // Cargar las relaciones 'afiliado' y la nueva 'zona'
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
     * (Tu método 'edit' está perfecto, solo añadimos las zonas)
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
        
        // Cambiamos la validación de 'zona' a 'zona_id'
        $validated = $request->validate([
            'codigo_medidor'  => 'required|string|unique:conexiones,codigo_medidor|max:50',
            'afiliado_id'     => 'required|exists:afiliados,id',
            'estado'          => 'required|in:activo,suspendido,eliminado',
            'direccion'       => 'required|string|max:255',
            'zona_id'         => 'required|exists:zonas,id', 
            'fecha_instalacion' => 'required|date',
            'tipo_conexion'   => 'required|in:domiciliaria,comercial,institucional,otro',
        ]);
        

       try {
        DB::transaction(function () use ($validated) {

            // 1) Crear la conexión
            $conexion = Conexion::create($validated);

            // 2) Buscar el afiliado
            $afiliado = Afiliado::find($validated['afiliado_id']);

            // 3) Si existe y está pendiente, pasarlo a activo
            if ($afiliado && $afiliado->estado_servicio === 'Pendiente') {
                $afiliado->update([
                    'estado_servicio' => 'activo',
                ]);
            }

        });
        } catch (\Exception $e) {
             Log::error('Error al guardar conexión:', ['error' => $e->getMessage(), 'request' => $request->all()]);
             return redirect()->back()->withInput()->withErrors(['error_general' => 'Error al guardar la conexión.']);
        }

        return redirect()->route('conexiones.index')->with('success', '✅ Conexión creada correctamente.');
    }


    /**
     * Actualiza una conexión.
     */
    public function update(Request $request, $id) 
    {
        $conexion = Conexion::findOrFail($id); 

        // --- MODIFICADO ---
        // Cambiamos la validación de 'zona' a 'zona_id'
        $validated = $request->validate([
            'codigo_medidor'  => ['required','string','max:50', Rule::unique('conexiones')->ignore($conexion->id)],
            'afiliado_id'     => 'required|exists:afiliados,id', 
            'estado'          => 'required|in:activo,suspendido,eliminado',
            'direccion'       => 'required|string|max:255',
            'zona_id'         => 'required|exists:zonas,id', // <-- CORREGIDO
            'fecha_instalacion' => 'required|date',
            'tipo_conexion'   => 'required|in:domiciliaria,comercial,institucional,otro',
        ]);
        // --- FIN MODIFICADO ---

        try {
            $conexion->update($validated); // Guardará los datos validados (incluyendo zona_id)
        } catch (\Exception $e) {
             Log::error('Error al actualizar conexión:', ['error' => $e->getMessage(), 'request' => $request->all()]);
            return redirect()->back()->withInput()->withErrors(['error_general' => 'Error al actualizar la conexión.']);
        }
        
        return redirect()->route('conexiones.index')->with('success', '✅ Conexión actualizada correctamente.');
    }
    
    /**
     * Elimina una conexión (Solo Admin).
     * (Modificado a $id para ser consistente)
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