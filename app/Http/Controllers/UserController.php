<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Afiliado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Illuminate\Validation\Rule; // ¡Importante para validación!
use Illuminate\Support\Facades\Auth; // ¡Importante para permisos!

class UserController extends Controller
{
    /**
     * Define los permisos para este controlador.
     * Solo 'Administrador' puede gestionar usuarios.
     */
    public function __construct()
    {
        // Esto protege TODAS las funciones en este controlador
        $this->middleware('role:Administrador');
    }

    /**
     * Muestra la lista paginada y filtrable de usuarios.
     * (Versión profesional y escalable)
     */
    public function index(Request $request)
    {
        $query = User::with(['afiliado:id,nombre_completo,ci', 'roles:id,name']); // Carga optimizada

        // 1. Búsqueda por Nombre o Email
        $query->when($request->input('search'), function ($q, $search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });

        // 2. Filtro por Rol
        $query->when($request->input('role'), function ($q, $roleName) {
            $q->whereHas('roles', function ($roleQuery) use ($roleName) {
                $roleQuery->where('name', $roleName);
            });
        });

        return Inertia::render('Users/Index', [
            'users' => $query->orderBy('name')
                             ->paginate(15) // ¡PAGINADO!
                             ->withQueryString(),
            'filters' => $request->only(['search', 'role']), // Devuelve filtros
            'roles' => Role::all()->pluck('name'), // Envía lista de roles para el dropdown
        ]);
    }

    /**
     * Muestra el formulario de creación.
     * Pasa los roles y la URL de la API para buscar afiliados.
     */
/**
     * Muestra el formulario de creación.
     */
        public function create()
        {
            $rolesPersonal = Role::whereIn('name', ['Administrador', 'Secretaria', 'Tecnico'])->get(['id', 'name']);
            $roleUsuario = Role::where('name', 'Usuario')->first(['id', 'name']);
            
            if (!$roleUsuario) {
                return redirect()->route('users.index')->withErrors(['error_general' => 'Error: El rol "Usuario" no existe.']);
            }

            return Inertia::render('Users/Create', [
                'rolesPersonal' => $rolesPersonal,
                'roleUsuario' => $roleUsuario,
                
                'searchAfiliadosUrl' => route('afiliados.buscarPorCI', ['ci' => '__CI_PLACEHOLDER__']),
            ]);
        }

    /**
     * Guarda un nuevo usuario.
     * (Usando tu lógica de 2 usuarios por afiliado)
     */
    public function store(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:afiliado,personal',
            'name' => 'required_if:tipo,personal|nullable|string|max:255', 
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed', 
            'role_id' => 'required|exists:roles,id',
            'afiliado_id' => 'required_if:tipo,afiliado|nullable|exists:afiliados,id',
        ]);

        $afiliado = null;
        if ($request->tipo === 'afiliado') {
            $afiliado = Afiliado::find($request->afiliado_id);
            if (!$afiliado) {
                return back()->withErrors(['afiliado_id' => 'Afiliado no encontrado.'])->withInput();
            }

            // lógica de límite de usuarios 
            $count = User::where('afiliado_id', $afiliado->id)->count();
            if ($count >= 2) {
                return back()->withErrors([
                    'afiliado_id' => 'Este afiliado ya tiene 2 usuarios (cuentas) asignados.'
                ])->withInput();
            }
        }

        $user = User::create([
            // Si es afiliado, el nombre es el del afiliado. Si es personal, es el del input.
            'name' => ($request->tipo === 'afiliado' && $afiliado) ? $afiliado->nombre_completo : $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'afiliado_id' => ($request->tipo === 'afiliado') ? $afiliado->id : null,
        ]);

        $role = Role::find($request->role_id);
        $user->assignRole($role); // Asigna el rol

        return redirect()->route('users.index')->with('success', '✅ Usuario creado correctamente.');
    }

    /**
     * Muestra el detalle de un usuario.
     */
    public function show(User $user)
    {
        $user->load(['afiliado:id,nombre_completo,ci', 'roles:id,name']); // Carga relaciones
        return Inertia::render('User/Show', [
            'user' => $user,
        ]);
    }


    /**
     * Muestra el formulario para editar el rol, email y contraseña del usuario.
     * ¡No carga Afiliado::all()!
     */
    public function edit(User $user)
    {
        // Carga solo lo necesario
        $user->load('afiliado:id,nombre_completo,ci', 'roles:id,name');
        
        // Cargar todos los roles disponibles para el dropdown de edición
        $allRoles = Role::all(['id', 'name']);

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'allRoles' => $allRoles,
        ]);
    }

    /**
     * Actualiza el usuario (Nombre, Email, Rol, Contraseña).
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|' . Rule::unique('users')->ignore($user->id),
            'password' => 'nullable|min:8|confirmed', // 'confirmed' para password_confirmation
            'role_id' => 'required|exists:roles,id',
            // No permitimos cambiar el afiliado_id desde aquí
        ]);
        
        // No se puede cambiar el rol del Super Admin (ID 1) o de uno mismo
        if ($user->id === 1 || $user->id === Auth::id()) {
             if ($validated['role_id'] != $user->roles->first()->id) {
                 return back()->withErrors(['role_id' => 'No se puede cambiar el rol de este usuario.']);
             }
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        // Actualizar contraseña SOLO si se envió una nueva
        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }
        
        // Sincronizar rol (más seguro)
        $role = Role::find($validated['role_id']);
        $user->syncRoles([$role->name]); // syncRoles espera el nombre

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
        
        return redirect()->route('users.index')
            ->with('success', 'Usuario eliminado.');
    }
}