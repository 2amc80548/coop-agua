<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Afiliado;
use App\Models\Zona;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Muestra la lista paginada y filtrable de usuarios.
     */
    public function index(Request $request)
    {
        $query = User::with(['afiliado:id,nombre_completo,ci', 'roles:id,name', 'zona:id,nombre']);

        // Búsqueda
        $query->when($request->input('search'), function ($q, $search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('afiliado', function ($afil) use ($search) {
                        $afil->where('ci', 'like', "%{$search}%")
                            ->orWhere('nombre_completo', 'like', "%{$search}%");
                    });
            });
        });

        // Filtro por Rol
        $query->when($request->input('role'), function ($q, $roleName) {
            $q->whereHas('roles', function ($roleQuery) use ($roleName) {
                $roleQuery->where('name', $roleName);
            });
        });

        return Inertia::render('Users/Index', [
            'users' => $query->orderBy('name')
                             ->paginate(10)
                             ->withQueryString(),
            'filters' => $request->only(['search', 'role']),
            'roles' => Role::all()->pluck('name'),
        ]);
    }

    /**
     * Muestra el formulario de creación.
     */
    public function create()
    {
        return Inertia::render('Users/Create', [
            'rolesPersonal' => Role::whereIn('name', ['Administrador', 'Secretaria', 'Tecnico'])->get(['id', 'name']),
            'roleUsuario' => Role::where('name', 'Usuario')->first(['id', 'name']),
            'zonas' => Zona::orderBy('nombre')->get(['id', 'nombre']),
            'searchAfiliadosUrl' => route('afiliados.buscarPorCI', ['ci' => '__CI_PLACEHOLDER__']),
        ]);
    }

    /**
     * Guarda un nuevo usuario.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:afiliado,personal',
            'name' => 'required|string|max:255', 
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'afiliado_id' => 'nullable|exists:afiliados,id',
            'is_active' => 'nullable|boolean',
            'zona_id' => 'nullable|exists:zonas,id',
        ]);

        $afiliadoId = $validated['afiliado_id'] ?? null;
        $role = Role::find($validated['role_id']);

        if ($role->name === 'Usuario' && $afiliadoId) {
            $count = User::where('afiliado_id', $afiliadoId)->count();
            if ($count >= 2) {
                return back()->withErrors(['afiliado_id' => 'Este afiliado ya tiene 2 usuarios asignados.'])->withInput();
            }
        }
        
        if ($role->name !== 'Usuario') {
            $afiliadoId = null;
        }

        $user = User::create([
            'name' => $validated['name'], 
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'afiliado_id' => $afiliadoId,
            'is_active' => $request->boolean('is_active', true),
            'zona_id' => $validated['zona_id'] ?? null,
        ]);

        $user->assignRole($role);

        ActivityLog::log(
            accion: 'crear',
            modulo: 'usuarios',
            descripcion: "Usuario '{$user->name}' ({$user->email}) creado con rol {$role->name}.",
            newValues: $user->toArray()
        );

        return redirect()->route('users.index')->with('success', '✅ Usuario creado correctamente.');
    }

    /**
     * Muestra el formulario para editar.
     */
    public function edit(User $user)
    {
        $user->load('afiliado:id,nombre_completo,ci', 'roles:id,name', 'zona:id,nombre');
        
        return Inertia::render('Users/Edit', [
            'user' => $user,
            'allRoles' => Role::all(['id', 'name']),
            'zonas' => Zona::orderBy('nombre')->get(['id', 'nombre']),
            'searchAfiliadosUrl' => route('afiliados.buscarPorCI', ['ci' => '__CI_PLACEHOLDER__']),
        ]);
    }

    /**
     * Actualiza un usuario.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|' . Rule::unique('users')->ignore($user->id),
            'password' => 'nullable|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'afiliado_id' => 'nullable|exists:afiliados,id',
            'is_active' => 'required|boolean',
            'zona_id' => 'nullable|exists:zonas,id',
        ]);
        
        $oldValues = $user->toArray();
        $role = Role::find($validated['role_id']);
        $afiliadoId = $validated['afiliado_id'] ?? null;
        
        if ($role->name === 'Usuario') {
            if ($afiliadoId) {
                $count = User::where('afiliado_id', $afiliadoId)
                             ->where('id', '!=', $user->id) 
                             ->count();
                if ($count >= 2) {
                    return back()->withErrors(['afiliado_id' => 'Este afiliado ya tiene 2 usuarios asignados.'])->withInput();
                }
            }
        } else {
            $afiliadoId = null;
        }

        $user->update([
            'name' => $validated['name'], 
            'email' => $validated['email'],
            'afiliado_id' => $afiliadoId,
            'is_active' => $request->boolean('is_active'),
            'zona_id' => $validated['zona_id'] ?? null,
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }
        
        $user->syncRoles([$role->name]);

        ActivityLog::log(
            accion: 'editar',
            modulo: 'usuarios',
            descripcion: "Usuario '{$user->name}' (ID #{$user->id}) actualizado.",
            oldValues: $oldValues,
            newValues: $user->fresh()->toArray()
        );

        return redirect()->route('users.index')->with('success', '✅ Usuario actualizado correctamente.');
    }

    /**
     * Elimina un usuario.
     */
    public function destroy(User $user)
    {
        if ($user->id === 1 || $user->id === Auth::id()) {
             return redirect()->route('users.index')
                ->withErrors(['error_general' => 'No se puede eliminar a este usuario administrador o a usted mismo.']);
        }

        $userValues = $user->toArray();
        $userName = $user->name;
        $userId = $user->id;
        
        $user->delete();

        ActivityLog::log(
            accion: 'eliminar',
            modulo: 'usuarios',
            descripcion: "Usuario '{$userName}' (ID #{$userId}) eliminado.",
            oldValues: $userValues
        );

        return redirect()->route('users.index')->with('success', 'Usuario eliminado.');
    }
}