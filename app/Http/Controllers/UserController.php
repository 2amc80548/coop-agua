<?php

namespace App\Http\Controllers;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Afiliado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;


class UserController extends Controller
{
    public function index()
    {
        $users = User::with('afiliado', 'roles')->get();
    
        return Inertia::render('Users/Index', [
            'users' => $users
        ]);
    }

    public function create()
    {
        return Inertia::render('Users/Create', [
            // Roles para personal
            'rolesPersonal' => Role::whereIn('name', ['administrador', 'secretaria', 'tecnico'])->get(),
    
            // Rol único para afiliado
            'roleUsuario' => Role::where('name', 'usuario')->first(),
        ]);
    }
// app/Http/Controllers/UserController.php

    public function store(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:afiliado,personal',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role_id' => 'required|exists:roles,id',
            'afiliado_id' => 'nullable|exists:afiliados,id',
        ]);

        // ✅ Solo si es afiliadao verifica el límite
        if ($request->tipo === 'afiliado') {
            $afiliado_id = $request->afiliado_id;

            // ✅ Cuenta cuántos usuarios YA existen para este afiliado
            $count = User::where('afiliado_id', $afiliado_id)->count();

            if ($count >= 2) {
                return back()->withErrors([
                    'afiliado_id' => 'Este afiliado ya tiene 2 usuarios asignados.'
                ])->withInput();
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'afiliado_id' => $request->afiliado_id,
        ]);

        $role = Role::find($request->role_id);
        $user->assignRole($role);

        return redirect()->route('users.index')->with('success', '✅ Usuario creado correctamente.');
    }
    public function edit(User $user)
    {
        // Carga el afiliado junto con el usuario
        $user->load('afiliado');

        $afiliado = afiliado::all()->map(fn($b) => [
            'id' => $b->id,
            'nombre_completo' => $b->nombre_completo
        ]);

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'afiliados' => $afiliado
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email,' . $user->id,
            'afiliado_id' => 'nullable|exists:afiliados,id',
        ]);

        $user->update([
            'name' => $request->name ?? optional(Afiliado::find($request->afiliado_id))->nombre_completo,
            'email' => $request->email,
            'afiliado_id' => $request->afiliado_id
        ]);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado.');
    }

    public function destroy(User $user)
    {
        $user->delete(); // o $user->forceDelete() si quieres eliminar permanentemente
    
        return redirect()->route('users.index')->with('success', 'Usuario eliminado y desvinculado.');
    }
}