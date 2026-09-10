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
use Carbon\Carbon; 

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
            $searchLower = mb_strtolower($search);
            $q->where(function ($w) use ($searchLower) {
                $w->where(DB::raw('LOWER(codigo_medidor)'), 'like', "%{$searchLower}%")
                  ->orWhereHas('afiliado', function ($afiliadoQuery) use ($searchLower) {
                      $afiliadoQuery->where(DB::raw('LOWER(ci)'), 'like', "%{$searchLower}%")
                                    ->orWhere(DB::raw('LOWER(nombre_completo)'), 'like', "%{$searchLower}%");
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
        $validated = $request->validate([
            'codigo_medidor'    => 'required|string|unique:conexiones,codigo_medidor|max:50',
            'afiliado_id'       => 'required|exists:afiliados,id',
            'estado'            => 'required|in:activo,suspendido,eliminado',
            'direccion'         => 'required|string|max:255',
            'zona_id'           => 'required|exists:zonas,id', 
            'fecha_instalacion' => 'required|date',
            'tipo_conexion'     => 'required|in:domiciliaria,comercial,institucional,otro',
            'lectura_inicial'   => 'required|numeric|min:0',
            'observacion'       => 'nullable|string|max:500',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                
                // 1. Crear la conexión de agua
                $conexion = \App\Models\Conexion::create([
                    'codigo_medidor'    => $validated['codigo_medidor'],
                    'afiliado_id'       => $validated['afiliado_id'],
                    'estado'            => $validated['estado'],
                    'direccion'         => $validated['direccion'],
                    'zona_id'           => $validated['zona_id'],
                    'fecha_instalacion' => $validated['fecha_instalacion'],
                    'tipo_conexion'     => $validated['tipo_conexion'],
                    'lectura_inicial'   => $validated['lectura_inicial'],
                ]);

                // 2. Lectura Inicial de Instalación (Consumo 0 m³)
                $lecturaInicialVal = (float) $validated['lectura_inicial'];
                $periodoInstalacion = Carbon::parse($validated['fecha_instalacion'])->format('Y-m');

                \App\Models\Lectura::create([
                    'conexion_id'      => $conexion->id,
                    'fecha_lectura'    => $validated['fecha_instalacion'],
                    'periodo'          => $periodoInstalacion, 
                    'lectura_anterior' => $lecturaInicialVal,
                    'lectura_actual'   => $lecturaInicialVal, // Mismo valor para que el consumo sea 0 m³
                    'observacion'      => $validated['observacion'] ?? 'Lectura de instalación de medidor (Inicial)',
                    'registrado_por'   => Auth::id(),
                    'estado'           => 'facturado', // Marcado como facturado con monto 0 para no cobrar pero servir de base
                ]);

                // 3. Activar afiliado si estaba pendiente
                $afiliado = \App\Models\Afiliado::find($validated['afiliado_id']);
                if ($afiliado && $afiliado->estado_servicio === 'Pendiente') {
                    $afiliado->update(['estado_servicio' => 'activo']);
                }

                \App\Models\ActivityLog::log(
                    accion: 'crear',
                    modulo: 'conexiones',
                    descripcion: "Conexión medidor #{$conexion->codigo_medidor} instalada con lectura inicial {$lecturaInicialVal} m³.",
                    newValues: $conexion->toArray()
                );

                return redirect()->route('conexiones.index')->with('success', '✅ Conexión creada correctamente con su lectura inicial.');
            });

        } catch (\Exception $e) {
            Log::error("Error al guardar conexión: " . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->withErrors(['error_general' => 'Fallo al registrar la conexión: ' . $e->getMessage()]);
        }
    }

    /**
     * Actualiza una conexión.
     */
    public function update(Request $request, $id) 
    {
        $conexion = Conexion::findOrFail($id); 

        $validated = $request->validate([
            'codigo_medidor'    => ['required','string','max:50', Rule::unique('conexiones')->ignore($conexion->id)],
            'afiliado_id'       => 'required|exists:afiliados,id', 
            'estado'            => 'required|in:activo,suspendido,eliminado',
            'direccion'         => 'required|string|max:255',
            'zona_id'           => 'required|exists:zonas,id', 
            'fecha_instalacion' => 'required|date',
            'tipo_conexion'     => 'required|in:domiciliaria,comercial,institucional,otro',
            'lectura_inicial'   => 'required|numeric|min:0',
        ]);

        try {
            $oldValues = $conexion->toArray();
            $conexion->update($validated);

            \App\Models\ActivityLog::log(
                accion: 'editar',
                modulo: 'conexiones',
                descripcion: "Conexión medidor #{$conexion->codigo_medidor} actualizada.",
                oldValues: $oldValues,
                newValues: $conexion->fresh()->toArray()
            );
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

        // Regla: No borrar si tiene lecturas operativas (excluyendo la inicial) o facturas
        $lecturasOperativas = $conexion->lecturas()->where('observacion', 'not like', '%inicial%')->where('observacion', 'not like', '%arranque%')->exists();

        if ($lecturasOperativas || $conexion->facturas()->exists()) {
             return redirect()->back()->withErrors(['error_general' => 'No se puede eliminar. La conexión ya tiene lecturas o facturas registradas.']);
        }

        try {
            DB::transaction(function () use ($conexion) {
                $conValues = $conexion->toArray();
                $medidor   = $conexion->codigo_medidor;
                $conexion->lecturas()->delete();
                $conexion->delete();

                \App\Models\ActivityLog::log(
                    accion: 'eliminar',
                    modulo: 'conexiones',
                    descripcion: "Conexión medidor #{$medidor} eliminada.",
                    oldValues: $conValues
                );
            });
        } catch (\Exception $e) {
            Log::error('Error al eliminar conexión:', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['error_general' => 'Error al eliminar la conexión.']);
        }
        
        return redirect()->back()->with('success', '🗑️ Conexión eliminada correctamente.');
    }
}