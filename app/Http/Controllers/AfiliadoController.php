<?php

namespace App\Http\Controllers;

// --- Imports necesarios ---
use App\Models\Afiliado;
use App\Models\User;
use App\Models\Zona;       // <-- ¡El modelo que creamos!
use App\Models\Requisito;  // <-- ¡El modelo que creamos!
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage; // <-- ¡Para las fotos!
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log; // Para registrar errores
use Carbon\Carbon; // Para manejar fechas

class AfiliadoController extends Controller
{
    /**
     * Define los permisos para cada acción del controlador.
     * Este es el "Guardia" profesional.
     */
    public function __construct()
    {
        // 1. Proteger TODAS las funciones. Solo Admin o Secretaria pueden entrar.
       // $this->middleware('role:Administrador|Secretaria');
        
        // 2. EXCEPCIÓN: El método 'destroy' (borrar) solo lo puede usar el Admin.
        $this->middleware('role:Administrador')->only('destroy');
        
        // 3. EXCEPCIÓN: La API 'buscarPorCI' la pueden usar también los Técnicos.
        //    (Necesario para el formulario de Conexiones)
        $this->middleware('role:Administrador|Secretaria|Tecnico')->only('buscarPorCI');
    }

    /**
     * Muestra la lista de afiliados con filtros.
     * (Actualizado para filtrar por 'zona_id' y 'estado_servicio')
     */
    public function index(Request $request)
    {
        // Cargar la relación 'zona' para mostrar el nombre
        $query = Afiliado::with('zona:id,nombre'); 

        // Búsqueda por Nombre o CI
        $query->when($request->input('search'), function ($q, $search) {
            $q->where('nombre_completo', 'like', "%{$search}%")
              ->orWhere('ci', 'like', "%{$search}%")
              ->orWhere('codigo', 'like', "%{$search}%");
        });

        // Filtro por Tipo (¡Nuevo!)
        $query->when($request->input('tipo'), function ($q, $tipo) {
            $q->where('tipo', $tipo);
        });

        // Filtro por Estado de Servicio (¡Nuevo!)
        $query->when($request->input('estado_servicio'), function ($q, $estado) {
            $q->where('estado_servicio', $estado);
        });
        
        // Filtro por Zona (¡Actualizado a zona_id!)
        $query->when($request->input('zona_id'), function ($q, $zonaId) {
            $q->where('zona_id', $zonaId);
        });

        return Inertia::render('Afiliados/Index', [
            'afiliados' => $query->orderBy('nombre_completo')->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'tipo', 'estado_servicio', 'zona_id']),
            // Enviar la lista de zonas para el dropdown de filtros
            'zonas' => Zona::orderBy('nombre')->get(['id', 'nombre']), 
        ]);
    }

    /**
     * Muestra el formulario de creación.
     * (Actualizado para enviar 'zonas' y 'requisitos')
     */
    public function create()
    {
        return Inertia::render('Afiliados/Create', [
            // Enviar la lista de zonas para el dropdown
            'zonas' => Zona::orderBy('nombre')->get(['id', 'nombre']),
            // Enviar la lista de requisitos (con los grupos) para los checkboxes
            'requisitos' => Requisito::orderBy('nombre')->get(), // Carga todos los campos
        ]);
    }

    /**
     * Guarda un nuevo afiliado (con foto, requisitos y usuario).
     */
    public function store(Request $request)
    {
        // 1. Validación (Actualizada)
        $validated = $request->validate([
            'nombre_completo'  => ['required','string','max:255'],
            'ci'               => ['required','string','max:50', Rule::unique('afiliados')],
            'celular'          => ['nullable','string','max:20'],
            'direccion'        => ['required','string','max:255'],
            'zona_id'          => ['required','integer', Rule::exists('zonas', 'id')], // ¡Actualizado!
            'tipo'             => ['required', Rule::in(['socio','usuario'])],
            'fecha_afiliacion' => ['nullable','date'],
            'codigo'           => ['required','string','max:50', Rule::unique('afiliados')],
            'tenencia'         => ['required', Rule::in(['propietario','compra_venta','posesion'])],
            'estado'           => ['required', Rule::in(['activo','suspendido','baja'])],
            'estado_servicio'  => ['required', Rule::in(['activo','en_corte','cortado'])],
            'adulto_mayor'     => ['boolean'],
            'profile_photo'    => ['nullable', 'image', 'max:2048'], // ¡Nuevo! Máx 2MB
            
            // Requisitos (¡Nuevo! Espera un array de IDs)
            'requisitos_seleccionados' => ['nullable', 'array'],
            'requisitos_seleccionados.*' => ['integer', Rule::exists('requisitos', 'id')],
            
            // Campos para el User
            'crear_usuario'    => ['boolean'],
            'email'            => ['required_if:crear_usuario,true', 'nullable', 'email', 'max:255', Rule::unique('users')],
            'password'         => ['required_if:crear_usuario,true', 'nullable', 'string', 'min:8'],
        ]);

        try {
            $afiliado = DB::transaction(function () use ($request, $validated) {
                
                // 2. Manejar subida de foto (¡Nuevo!)
                $photoPath = null;
                if ($request->hasFile('profile_photo')) {
                    // Guarda en storage/app/public/fotos_afiliados
                    $photoPath = $request->file('profile_photo')->store('fotos_afiliados', 'public');
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
                    'profile_photo_path' => $photoPath, // Guardar la ruta
                ]);

                // 4. Guardar Requisitos (¡Nuevo sistema!)
                if (!empty($validated['requisitos_seleccionados'])) {
                    $fechaHoy = Carbon::now()->toDateString();
                    // Prepara los datos para la tabla pivot (incluyendo la fecha)
                    $requisitosConData = collect($validated['requisitos_seleccionados'])->mapWithKeys(function ($id) use ($fechaHoy) {
                        return [$id => ['fecha_entrega' => $fechaHoy, 'observacion' => 'Entregado al registrar']]; 
                    });
                    // attach() es el método para guardar en tabla pivot (muchos a muchos)
                    $afiliado->requisitos()->attach($requisitosConData);
                }

                // 5. Crear Usuario (si se marcó la opción)
                if ($request->boolean('crear_usuario') && !empty($validated['email'])) {
                    $user = User::create([
                        'name'        => $validated['nombre_completo'],
                        'email'       => $validated['email'],
                        'password'    => Hash::make($validated['password']),
                        'afiliado_id' => $afiliado->id,
                    ]);
                    $user->assignRole('Usuario');
                }
                
                return $afiliado;
            });

        } catch (\Exception $e) {
            Log::error('Error al guardar afiliado:', ['error' => $e->getMessage(), 'request' => $request->all()]);
            // Borrar la foto si se subió pero la transacción falló
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
     * (Actualizado para cargar 'zona' y 'requisitos')
     */
    public function show(Afiliado $afiliado)
    {
        // Carga las relaciones que definimos en los modelos
        // 'requisitos' ahora carga la info de la tabla pivot
      $afiliado->load(['zona', 'requisitos', 'conexiones', 'conexiones.zona', 'user']); 
        
        return Inertia::render('Afiliados/Show', [
            'afiliado' => $afiliado
        ]);
    }

    /**
     * Muestra el formulario de edición.
     * (Actualizado para cargar 'zonas' y 'requisitos')
     */
    public function edit(Afiliado $afiliado)
    {
        // Cargar los requisitos que el afiliado YA TIENE
        $afiliado->load('requisitos'); 

        return Inertia::render('Afiliados/Edit', [
            'afiliado' => $afiliado,
            'zonas' => Zona::orderBy('nombre')->get(['id', 'nombre']), // Lista completa de zonas
            // Lista completa de requisitos (con los grupos)
            'requisitos' => Requisito::orderBy('nombre')->get(), // Carga todos los campos
        ]);
    }

    /**
     * Actualiza un afiliado (con lógica de seguridad).
     */
    public function update(Request $request, Afiliado $afiliado)
    {
        // 1. Lógica de Seguridad para "Baja" (¡Tu idea!)
        if ($request->input('estado') === 'baja' && $afiliado->estado !== 'baja' && !$request->boolean('force_baja')) {
            // Si el estado es 'baja' PERO no viene la confirmación 'force_baja'
            // Devolver un error de confirmación a Vue
             return redirect()->back()->withInput()
                 ->with('confirmar_baja', '¿Está seguro de dar de baja a este afiliado? Ya no aparecerá en nuevas lecturas.');
        }
        
        // 2. Lógica de Seguridad para Campos Protegidos (¡Tu idea!)
        $user = Auth::user();
        $reglasCi = ['required','string','max:50', Rule::unique('afiliados')->ignore($afiliado->id)];
        $reglasFechaAfiliacion = ['nullable','date'];

        if (!$user->hasRole('Administrador')) {
             // Si NO es Admin, CI y Fecha Afiliación no deben cambiar
             $reglasCi[] = Rule::in([$afiliado->ci]);
             $reglasFechaAfiliacion[] = Rule::in([$afiliado->fecha_afiliacion ? $afiliado->fecha_afiliacion->format('Y-m-d') : null]);
        }

        // 3. Validación (Similar a 'store' pero ajustada)
        $validated = $request->validate([
            'nombre_completo'  => ['required','string','max:255'],
            'ci'               => $reglasCi, // Regla dinámica
            'celular'          => ['nullable','string','max:20'],
            'direccion'        => ['required','string','max:255'],
            'zona_id'          => ['required','integer', Rule::exists('zonas', 'id')],
            'tipo'             => ['required', Rule::in(['socio','usuario'])],
            'fecha_afiliacion' => $reglasFechaAfiliacion, // Regla dinámica
            'fecha_baja'       => ['nullable','date', 'after_or_equal:fecha_afiliacion'],
            'codigo'           => ['required','string','max:50', Rule::unique('afiliados')->ignore($afiliado->id)], // Editable
            'tenencia'         => ['required', Rule::in(['propietario','compra_venta','posesion'])],
            'estado'           => ['required', Rule::in(['activo','suspendido','baja'])],
            'estado_servicio'  => ['required', Rule::in(['activo','en_corte','cortado'])],
            'adulto_mayor'     => ['boolean'],
            'profile_photo'    => ['nullable', 'image', 'max:2048'], // Foto nueva (opcional)
            'clear_photo'      => ['boolean'], // Opción para borrar foto
            'requisitos_seleccionados' => ['nullable', 'array'],
            'requisitos_seleccionados.*' => ['integer', Rule::exists('requisitos', 'id')],
        ], [
            // Mensajes de error para campos protegidos
            'ci.in' => 'No tiene permisos para modificar el CI.',
            'fecha_afiliacion.in' => 'No tiene permisos para modificar la Fecha de Afiliación.',
        ]);
        
        try {
            DB::transaction(function () use ($afiliado, $request, $validated) {
                
                $updateData = $validated;
                unset($updateData['profile_photo']); // Quitar para no guardar el objeto temporal
                unset($updateData['clear_photo']);
                unset($updateData['requisitos_seleccionados']);

                // 4. Manejar actualización de foto
                if ($request->hasFile('profile_photo')) {
                    if ($afiliado->profile_photo_path) {
                        Storage::disk('public')->delete($afiliado->profile_photo_path);
                    }
                    $updateData['profile_photo_path'] = $request->file('profile_photo')->store('fotos_afiliados', 'public');
                } else if ($request->boolean('clear_photo')) {
                     if ($afiliado->profile_photo_path) {
                        Storage::disk('public')->delete($afiliado->profile_photo_path);
                    }
                    $updateData['profile_photo_path'] = null;
                }

                // 5. Actualizar Afiliado
                $afiliado->update($updateData);

                // 6. Sincronizar Requisitos (¡Forma profesional!)
                // sync() borra los requisitos que ya no están seleccionados y añade los nuevos.
                if (array_key_exists('requisitos_seleccionados', $validated)) {
                    $fechaHoy = Carbon::now()->toDateString();
                    $syncData = [];
                    foreach ($validated['requisitos_seleccionados'] as $reqId) {
                        // Verificar si ya lo tenía, para no sobrescribir la fecha/obs
                        $pivotExistente = $afiliado->requisitos()->where('requisito_id', $reqId)->first()?->pivot;
                        $syncData[$reqId] = [
                            'fecha_entrega' => $pivotExistente?->fecha_entrega ?? $fechaHoy,
                            // 'observacion'   => $pivotExistente?->observacion ?? 'Actualizado', // Puedes añadir un campo para esto en Edit.vue
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
        // Regla: No borrar si tiene conexiones activas.
        if ($afiliado->conexiones()->where('estado', '!=', 'baja')->count() > 0) { // Asumiendo que 'baja' es un estado en conexiones
             return redirect()->back()->withErrors(['error_general' => 'No se puede eliminar. El afiliado aún tiene conexiones activas.']);
        }
        
        // (Opcional) Regla: No borrar si tiene deudas
        // if ($afiliado->conexiones()->whereHas('facturas', fn($q) => $q->where('estado', 'impaga'))->count() > 0) { ... }

        try {
            DB::transaction(function () use ($afiliado) {
                // Borrar la foto del storage si existe
                if ($afiliado->profile_photo_path) {
                    Storage::disk('public')->delete($afiliado->profile_photo_path);
                }
                // Los requisitos (tabla pivot) y usuario (afiliado_id=null) se manejan por las FK de la BD
                $afiliado->delete();
            });
        } catch (\Exception $e) {
             Log::error('Error al eliminar afiliado:', ['error' => $e->getMessage(), 'afiliado_id' => $afiliado->id]);
             return redirect()->back()->withErrors(['error_general' => 'Error al eliminar. Puede estar protegido por otras tablas (pagos, etc).']);
        }
        
        return redirect()->route('afiliados.index')->with('success', '🗑️ Afiliado eliminado permanentemente.');
    }

    /**
     * API para buscar afiliados por CI (usada en Conexiones/Create).
     * (Actualizado para devolver 'zona_id')
     */
    public function buscarPorCI($ci)
    {
        // Buscamos un afiliado que NO esté 'de baja'
        $afiliado = Afiliado::where('ci', $ci)->where('estado', '!=', 'baja')->first();
        if (!$afiliado) {
            return response()->json(null, 404);
        }
        return response()->json([
            'id'              => $afiliado->id,
            'nombre_completo' => $afiliado->nombre_completo,
            'ci'              => $afiliado->ci,
            'direccion'       => $afiliado->direccion,
            'zona_id'         => $afiliado->zona_id, // ¡Actualizado!
        ]);
    }
}