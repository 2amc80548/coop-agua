<?php

namespace App\Http\Controllers;

// --- Imports necesarios ---
use App\Models\Afiliado;
use App\Models\User;
use App\Models\Zona;
use App\Models\Requisito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AfiliadoController extends Controller
{
    /**
     * Muestra la lista de afiliados con filtros.
     */
    public function index(Request $request)
    {
        $query = Afiliado::with('zona:id,nombre'); 

        $query->when($request->input('search'), function ($q, $search) {
            $q->where('nombre_completo', 'like', "%{$search}%")
              ->orWhere('ci', 'like', "%{$search}%")
              ->orWhere('codigo', 'like', "%{$search}%");
        });
        $query->when($request->input('tipo'), fn($q, $v) => $q->where('tipo', $v));
        
        $query->when($request->input('estado_servicio'), fn($q, $v) => $q->where('estado_servicio', $v)); 

$query->when($request->filled('adulto_mayor'), function ($q) use ($request) {
    $q->where('adulto_mayor', $request->input('adulto_mayor') === '1');
});
        
        $query->when($request->input('zona_id'), fn($q, $v) => $q->where('zona_id', $v));

        
        return Inertia::render('Afiliados/Index', [
            'afiliados' => $query->orderBy('nombre_completo')->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'tipo', 'estado_servicio', 'zona_id']),
            'zonas' => Zona::orderBy('nombre')->get(['id', 'nombre']), 
        ]);
    }

    /**
     * Muestra el formulario de creación.
     */
    public function create()
    {
        return Inertia::render('Afiliados/Create', [
            'zonas' => Zona::orderBy('nombre')->get(['id', 'nombre']),
            'requisitos' => Requisito::orderBy('nombre')->get(), // Carga todos los campos (incluye es_para_...)
        ]);
    }

    /**
     * Guarda un nuevo afiliado (¡CORREGIDO!)
     */
    public function store(Request $request)
    {
        // 1. Validación (¡Actualizada!)
        $validated = $request->validate([
            'nombre_completo'  => ['required','string','max:255'],
            'ci'               => ['required','string','max:50', Rule::unique('afiliados')],
            'celular'          => ['nullable','string','max:20'],
            'direccion'        => ['required','string','max:255'], 
            'zona_id'          => ['required','integer', Rule::exists('zonas', 'id')], 
            'tipo'             => ['required', Rule::in(['socio','usuario'])],
            'fecha_afiliacion' => ['nullable','date'],
            'codigo'           => ['required','string','max:50', Rule::unique('afiliados')],
            'tenencia'         => ['required', Rule::in(['propietario','compra_venta','posesion'])],
            'estado'           => ['required', Rule::in(['activo','suspendido','baja'])],
            'estado_servicio'  => ['required', Rule::in(['activo','en_corte','cortado','Pendiente'])],
            'adulto_mayor'     => ['boolean'],
            'profile_photo'    => ['nullable', 'image', 'max:2048'],
            'observacion'      => ['nullable', 'string', 'max:2000'], 
            
            'requisitos_seleccionados' => ['nullable', 'array'],
            'requisitos_seleccionados.*' => ['integer', Rule::exists('requisitos', 'id')],
            
          
        ]);

        try {
            $afiliado = DB::transaction(function () use ($request, $validated) {
                
                $photoPath = null;
                if ($request->hasFile('profile_photo')) {       
                    $file = $request->file('profile_photo');
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $filename = $originalName . '_' . now()->format('Ymd_His') . '.' . $extension;
                    $photoPath = $file->storeAs('fotos_afiliados', $filename, 'public');
                }

                // 3. Crear el Afiliado
                $afiliado = Afiliado::create([
                    'nombre_completo' => $validated['nombre_completo'],
                    'ci' => $validated['ci'],
                    'celular' => $validated['celular'],
                    'direccion' => $validated['direccion'],
                    'zona_id' => $validated['zona_id'],
                    'tipo' => $validated['tipo'],
                    'fecha_afiliacion' => $validated['fecha_afiliacion'],
                    'codigo' => $validated['codigo'],
                    'tenencia' => $validated['tenencia'],
                    'estado' => $validated['estado'],
                    'estado_servicio' => $validated['estado_servicio'], 
                    'adulto_mayor' => $validated['adulto_mayor'] ?? false,
                    'profile_photo_path' => $photoPath,
                    'observacion' => $validated['observacion'], 
                ]);

                // 4. Guardar Requisitos
                if (!empty($validated['requisitos_seleccionados'])) {
                    $fechaHoy = Carbon::now()->toDateString();
                    $requisitosConData = collect($validated['requisitos_seleccionados'])->mapWithKeys(function ($id) use ($fechaHoy) {
                        return [$id => ['fecha_entrega' => $fechaHoy, 'observacion' => 'Entregado al registrar']]; 
                    });
                    $afiliado->requisitos()->attach($requisitosConData);
                }
                
                // (Lógica de crear usuario eliminada)
                
                return $afiliado;
            });

        } catch (\Exception $e) {
            Log::error('Error al guardar afiliado:', ['error' => $e->getMessage(), 'request' => $request->all()]);
             if (isset($photoPath) && Storage::disk('public')->exists($photoPath)) {
                 Storage::disk('public')->delete($photoPath);
             }
            return redirect()->back()->withInput()
                ->withErrors(['error_general' => 'Error al guardar el afiliado: ' . $e->getMessage()]);
        }

        return redirect()->route('afiliados.index')
            ->with('success', '✅ Afiliado creado correctamente.');
    }

    /**
     * Muestra el detalle de un afiliado.
     */
    public function show(Afiliado $afiliado)
    {
        $afiliado->load(['zona', 'requisitos', 'conexiones.zona', 'user']); 
        
        return Inertia::render('Afiliados/Show', [
            'afiliado' => $afiliado
        ]);
    }

    /**
     * Muestra el formulario de edición.
     */
    public function edit(Afiliado $afiliado)
    {
        $afiliado->load('requisitos'); 

        return Inertia::render('Afiliados/Edit', [
            'afiliado' => $afiliado,
            'zonas' => Zona::orderBy('nombre')->get(['id', 'nombre']),
            'requisitos' => Requisito::orderBy('nombre')->get(),
        ]);
    }

    /**
     * Actualiza un afiliado (¡CORREGIDO!)
     */
    public function update(Request $request, Afiliado $afiliado)
    {
        if ($request->input('estado') === 'baja' && $afiliado->estado !== 'baja' && !$request->boolean('force_baja')) {
             return redirect()->back()->withInput()
                 ->with('confirmar_baja', '¿Está seguro de dar de baja a este afiliado? Ya no aparecerá en nuevas lecturas.');
        }
        
        $user = Auth::user();
        $reglasCi = ['required','string','max:50', Rule::unique('afiliados')->ignore($afiliado->id)];
        $reglasFechaAfiliacion = ['nullable','date'];

        if (!$user->hasRole('Administrador')) {
             if ($request->input('ci') !== $afiliado->ci) {
                 $reglasCi[] = Rule::in([$afiliado->ci]);
             }
             if ($request->input('fecha_afiliacion') !== ($afiliado->fecha_afiliacion ? $afiliado->fecha_afiliacion->format('Y-m-d') : null)) {
                 $reglasFechaAfiliacion[] = Rule::in([$afiliado->fecha_afiliacion ? $afiliado->fecha_afiliacion->format('Y-m-d') : null]);
             }
        }

        // 3. Validación (¡Actualizada!)
        $validated = $request->validate([
            'nombre_completo'  => ['required','string','max:255'],
            'ci'               => $reglasCi,
            'celular'          => ['nullable','string','max:20'],
            'direccion'        => ['required','string','max:255'],
            'zona_id'          => ['required','integer', Rule::exists('zonas', 'id')],
            'tipo'             => ['required', Rule::in(['socio','usuario'])],
            'fecha_afiliacion' => $reglasFechaAfiliacion,
            'fecha_baja'       => ['nullable','date', 'after_or_equal:fecha_afiliacion'],
            'codigo'           => ['required','string','max:50', Rule::unique('afiliados')->ignore($afiliado->id)],
            'tenencia'         => ['required', Rule::in(['propietario','compra_venta','posesion'])],
            'estado'           => ['required', Rule::in(['activo','suspendido','baja'])],
            'estado_servicio'  => ['required', Rule::in(['activo','en_corte','cortado','Pendiente'])],
            'adulto_mayor'     => ['boolean'],
            'profile_photo'    => ['nullable', 'image', 'max:2048'],
            'clear_photo'      => ['boolean'],
            'observacion'      => ['nullable', 'string', 'max:2000'], 
            'requisitos_seleccionados' => ['nullable', 'array'],
            'requisitos_seleccionados.*' => ['integer', Rule::exists('requisitos', 'id')],
        ], [
            'ci.in' => 'No tiene permisos para modificar el CI.',
            'fecha_afiliacion.in' => 'No tiene permisos para modificar la Fecha de Afiliación.',
        ]);
        
        try {
            DB::transaction(function () use ($afiliado, $request, $validated) {
                
                $updateData = $validated;
                unset($updateData['profile_photo']);
                unset($updateData['clear_photo']);
                unset($updateData['requisitos_seleccionados']);

                if ($request->hasFile('profile_photo')) {
                    if ($afiliado->profile_photo_path && Storage::disk('public')->exists($afiliado->profile_photo_path)) {
                        Storage::disk('public')->delete($afiliado->profile_photo_path);
                    }
                    $file = $request->file('profile_photo');
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $filename = $originalName . '_' . now()->format('Ymd_His') . '.' . $extension;
                    $updateData['profile_photo_path'] = $file->storeAs('fotos_afiliados', $filename, 'public');

                } else if ($request->boolean('clear_photo')) {
                     if ($afiliado->profile_photo_path && Storage::disk('public')->exists($afiliado->profile_photo_path)) {
                        Storage::disk('public')->delete($afiliado->profile_photo_path);
                    }
                    $updateData['profile_photo_path'] = null;
                }

                // 5. Actualizar Afiliado
                $afiliado->update($updateData);

                // 6. Sincronizar Requisitos
                if (array_key_exists('requisitos_seleccionados', $validated)) {
                    $fechaHoy = Carbon::now()->toDateString();
                    $syncData = [];
                    foreach ($validated['requisitos_seleccionados'] as $reqId) {
                        $pivotExistente = $afiliado->requisitos()->where('requisito_id', $reqId)->first()?->pivot;
                        $syncData[$reqId] = [
                            'fecha_entrega' => $pivotExistente?->fecha_entrega ?? $fechaHoy,
                        ];
                    }
                    $afiliado->requisitos()->sync($syncData);
                }
            });

        } catch (\Exception $e) {
            Log::error('Error al actualizar afiliado:', ['error' => $e->getMessage(), 'request' => $request->all()]);
             return redirect()->back()->withInput()
                ->withErrors(['error_general' => 'Error al actualizar: ' . $e->getMessage()]);
        }

        return redirect()->route('afiliados.index')
            ->with('success', '✅ Afiliado actualizado correctamente.');
    }

    /**
     * Elimina un afiliado (solo Admin).
     */
    public function destroy(Afiliado $afiliado)
    {
        if ($afiliado->conexiones()->where('estado', '!=', 'baja')->count() > 0) {
             return redirect()->back()->withErrors(['error_general' => 'No se puede eliminar. El afiliado aún tiene conexiones activas.']);
        }
        
        try {
            DB::transaction(function () use ($afiliado) {
                if ($afiliado->profile_photo_path) {
                    Storage::disk('public')->delete($afiliado->profile_photo_path);
                }
                $afiliado->delete();
            });
        } catch (\Exception $e) {
             Log::error('Error al eliminar afiliado:', ['error' => $e->getMessage(), 'afiliado_id' => $afiliado->id]);
             return redirect()->back()->withErrors(['error_general' => 'Error al eliminar. Puede estar protegido por otras tablas.']);
        }
        
        return redirect()->route('afiliados.index')->with('success', '🗑️ Afiliado eliminado permanentemente.');
    }

    /**
     * API para buscar afiliados por CI (usada en Conexiones/Create).
     */
    public function buscarPorCI($ci)
    {
        $afiliado = Afiliado::where('ci', $ci)->where('estado', '!=', 'baja')->first();
        if (!$afiliado) {
            return response()->json(null, 404);
        }
        return response()->json([
            'id'              => $afiliado->id,
            'nombre_completo' => $afiliado->nombre_completo,
            'ci'              => $afiliado->ci,
            'direccion'       => $afiliado->direccion,
            'zona_id'         => $afiliado->zona_id,
        ]);
    }
}