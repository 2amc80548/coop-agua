<?php

namespace App\Http\Controllers;

use App\Models\Reclamo;
use App\Models\ReclamoTipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReclamoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth'); 
    }

    /**
     * Muestra la lista de reclamos (para Admin/Secretaria).
     */
    public function index(Request $request)
    {
        $query = Reclamo::with([
            'afiliado:id,nombre_completo,ci',
            'tipo:id,nombre',
            'usuario:id,name'
        ]);

        $estadoFiltro = $request->input('estado', 'Abierto');
        if ($estadoFiltro !== 'todos') {
            $query->where('estado', $estadoFiltro);
        }

        $query->when($request->input('reclamo_tipo_id'), function ($q, $tipoId) {
            $q->where('reclamo_tipo_id', $tipoId);
        });

        return Inertia::render('Reclamos/Index', [
            'reclamos' => $query->orderBy('created_at', 'desc')
                                 ->paginate(15)
                                 ->withQueryString(),
            'filters' => [
                'estado' => $estadoFiltro,
                'reclamo_tipo_id' => $request->input('reclamo_tipo_id'),
            ],
            'reclamoTipos' => ReclamoTipo::all(['id', 'nombre']),
        ]);
    }

    /**
     * Muestra la lista de reclamos del usuario logueado.
     */
    public function usuarioIndex(Request $request)
    {
        $user = Auth::user();

        if (!$user->afiliado_id) {
            return Inertia::render('Usuario/Reclamos/Index', [
                'reclamos' => ['data' => []],
                'error' => 'Tu cuenta no está vinculada a un afiliado.'
            ]);
        }

        $query = Reclamo::with('tipo:id,nombre')
                        ->where('afiliado_id', $user->afiliado_id);

        $query->when($request->input('estado'), function ($q, $estado) {
            $q->where('estado', $estado);
        });

        return Inertia::render('Usuario/Reclamos/Index', [
            'reclamos' => $query->orderBy('created_at', 'desc')
                                 ->paginate(10)
                                 ->withQueryString(),
            'filters' => $request->only(['estado']),
        ]);
    }

    /**
     * Formulario de creación de reclamo (Usuario).
     */
    public function create()
    {
        $user = Auth::user();
        $conexiones = $user->afiliado ? $user->afiliado->conexiones : [];

        if (count($conexiones) === 0 && !$user->afiliado_id) {
            return redirect()->route('usuario.dashboard')
                ->withErrors(['error_general' => 'No tienes un afiliado o conexiones activas para registrar un reclamo.']);
        }

        return Inertia::render('Usuario/Reclamos/Create', [
            'reclamoTipos' => ReclamoTipo::orderBy('nombre')->get(['id', 'nombre']),
            'conexiones' => $conexiones,
        ]);
    }

    /**
     * Guarda un nuevo reclamo (Usuario).
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->afiliado_id) {
            abort(403, 'Usuario no vinculado a un afiliado.');
        }

        $validated = $request->validate([
            'reclamo_tipo_id' => 'required|exists:reclamo_tipos,id',
            'conexion_id'     => 'nullable|exists:conexiones,id',
            'asunto'          => 'required|string|max:255',
            'mensaje_usuario' => 'required|string|min:20|max:2000',
        ]);

        if ($validated['conexion_id']) {
            $conexion = $user->afiliado->conexiones()->find($validated['conexion_id']);
            if (!$conexion) {
                return back()->withErrors(['conexion_id' => 'Esa conexión no le pertenece.']);
            }
        }

        try {
            Reclamo::create([
                'afiliado_id'     => $user->afiliado_id,
                'user_id'         => $user->id,
                'reclamo_tipo_id' => $validated['reclamo_tipo_id'],
                'conexion_id'     => $validated['conexion_id'] ?? null,
                'asunto'          => $validated['asunto'],
                'mensaje_usuario' => $validated['mensaje_usuario'],
                'estado'          => 'Abierto',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al guardar reclamo:', ['error' => $e->getMessage()]);
            return redirect()->back()->withInput()
                ->withErrors(['error_general' => 'No se pudo registrar el reclamo. Intente más tarde.']);
        }

        return redirect()->route('reclamos.usuarioIndex')
                         ->with('success', '✅ Reclamo enviado correctamente.');
    }

    /**
     * Muestra el detalle del reclamo (Admin o Usuario).
     */
    public function show(Reclamo $reclamo)
    {
        $user = Auth::user();

        if ($user->hasRole('Usuario') && $reclamo->afiliado_id !== $user->afiliado_id) {
            abort(403, 'No tiene permiso para ver este reclamo.');
        }

        $reclamo->load([
            'afiliado:id,nombre_completo,ci',
            'tipo:id,nombre',
            'usuario:id,name',
            'resueltoPor:id,name',
            'conexion:id,codigo_medidor,direccion'
        ]);

        return Inertia::render('Reclamos/Show', [
            'reclamo' => $reclamo,
        ]);
    }

    /**
     * Actualiza un reclamo (respuesta de Admin/Secretaria).
     */
    public function update(Request $request, Reclamo $reclamo)
    {
        $validated = $request->validate([
            'respuesta_admin' => 'required|string|min:10|max:2000',
            'estado'          => ['required', Rule::in(['En Revisión', 'Resuelto', 'Cerrado'])],
        ]);

        try {
            $reclamo->update([
                'respuesta_admin' => $validated['respuesta_admin'],
                'estado'          => $validated['estado'],
                'resuelto_por_user_id' => Auth::id(),
                'updated_at'      => Carbon::now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al responder reclamo:', ['error' => $e->getMessage()]);
            return redirect()->back()->withInput()
                ->withErrors(['error_general' => 'No se pudo guardar la respuesta.']);
        }

        return redirect()->route('reclamos.index', $reclamo->id)
                         ->with('success', '✅ Respuesta enviada correctamente.');
    }

    /**
     * Reabrir un reclamo cerrado (Admin/Secretaria).
     */
    public function reabrir(Reclamo $reclamo)
    {
        $reclamo->update([
            'estado' => 'En Revisión',
        ]);

        return redirect()->route('reclamos.show', $reclamo->id)
                         ->with('success', 'Reclamo re-abierto.');
    }
}
