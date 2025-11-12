<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Afiliado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Define los permisos para este controlador.
     */
    // public function __construct()
    // {
    //     $this->middleware('role:Administrador');
    // }

    /**
     * Muestra la lista paginada y filtrable de usuarios.
     */
    public function index(Request $request)
    {
        $query = User::with(['afiliado:id,nombre_completo,ci', 'roles:id,name']);

        // Búsqueda
        $query->when($request->input('search'), function ($q, $search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });

        // Filtro por Rol
        $query->when($request->input('role'), function ($q, $roleName) {
            $q->whereHas('roles', function ($roleQuery) use ($roleName) {
                $roleQuery->where('name', $roleName);
            });
        });

        return Inertia::render('Users/Index', [
            'users' => $query->orderBy('name')
                             ->paginate(15)
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
            'searchAfiliadosUrl' => route('afiliados.buscarPorCI', ['ci' => '__CI_PLACEHOLDER__']),
        ]);
    }

    /**
     * Guarda un nuevo usuario.
     * (¡CORREGIDO! El 'name' es independiente del afiliado)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:afiliado,personal',
            'name' => 'required|string|max:255', // ¡AHORA SIEMPRE REQUERIDO!
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'afiliado_id' => 'nullable|exists:afiliados,id', // Nullable
        ]);

        $afiliadoId = $validated['afiliado_id'] ?? null;
        $role = Role::find($validated['role_id']);

        // Si el rol es 'afiliado' (Usuario) y se seleccionó un afiliado...
        if ($role->name === 'Usuario' && $afiliadoId) {
            // Validamos el límite de 2 usuarios
            $count = User::where('afiliado_id', $afiliadoId)->count();
            if ($count >= 2) {
                return back()->withErrors(['afiliado_id' => 'Este afiliado ya tiene 2 usuarios (cuentas) asignados.'])->withInput();
            }
        }
        
        // Si el rol es 'personal', nos aseguramos de desvincular
        if ($role->name !== 'Usuario') {
            $afiliadoId = null;
        }

        $user = User::create([
            'name' => $validated['name'], // ¡USA EL NOMBRE DEL FORMULARIO!
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'afiliado_id' => $afiliadoId,
        ]);

        $user->assignRole($role);

        return redirect()->route('users.index')->with('success', '✅ Usuario creado correctamente.');
    }

    /**
     * Muestra el formulario para editar.
     * (¡ACTUALIZADO!)
     */
    public function edit(User $user)
    {
        $user->load('afiliado:id,nombre_completo,ci', 'roles:id,name');
        
        return Inertia::render('Users/Edit', [
            'user' => $user,
            'allRoles' => Role::all(['id', 'name']),
            // ¡AÑADIDO! Pasa la URL de la API para el buscador
            'searchAfiliadosUrl' => route('afiliados.buscarPorCI', ['ci' => '__CI_PLACEHOLDER__']),
        ]);
    }

    /**
     * Actualiza un usuario.
     * (¡ACTUALIZADO! Permite asignar afiliado y editar nombre)
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|' . Rule::unique('users')->ignore($user->id),
            'password' => 'nullable|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'afiliado_id' => 'nullable|exists:afiliados,id', // ¡Permite asignar/cambiar!
        ]);
        
        $role = Role::find($validated['role_id']);
        $afiliadoId = $validated['afiliado_id'] ?? null;
        
        // Si el rol es "Usuario"
        if ($role->name === 'Usuario') {
            if ($afiliadoId) {
                 // Si se asignó uno, validamos el límite (excluyéndonos a nosotros mismos)
                $count = User::where('afiliado_id', $afiliadoId)
                             ->where('id', '!=', $user->id) // Excluirse a sí mismo
                             ->count();
                if ($count >= 2) {
                    return back()->withErrors(['afiliado_id' => 'Este afiliado ya tiene 2 usuarios (cuentas) asignados.'])->withInput();
                }
            }
            // Si $afiliadoId es null, no pasa nada (es un usuario "Pendiente")
        } else {
            // Si el rol es Admin, Sec, Tec, nos aseguramos que no esté vinculado
            $afiliadoId = null;
        }

        // Actualizar el usuario
        $user->update([
            'name' => $validated['name'], // ¡USA EL NOMBRE DEL FORMULARIO!
            'email' => $validated['email'],
            'afiliado_id' => $afiliadoId, // Guardar el ID
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }
        
        $user->syncRoles([$role->name]); // Sincronizar el rol

        return redirect()->route('users.index')->with('success', 'Usuario actualizado.');
    }

    /**
     * Elimina un usuario.
     */
    public function destroy(User $user)
    {
        // Regla de Seguridad: No permitir borrar el usuario ID 1 (super admin) o a uno mismo
        if ($user->id === 1 || $user->id === Auth::id()) {
             return redirect()->route('users.index')
                ->withErrors(['error_general' => 'No se puede eliminar a este usuario administrador o a usted mismo.']);
        }
        
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Usuario eliminado.');
    }
}