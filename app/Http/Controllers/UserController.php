<?php

namespace App\Http\Controllers;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Beneficiario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;


class UserController extends Controller
{
    public function index()
    {
        $users = User::with('beneficiario', 'roles')->get();
    
        return Inertia::render('Users/Index', [
            'users' => $users
        ]);
    }

    public function create()
    {
        return Inertia::render('Users/Create', [
            // Roles para personal
            'rolesPersonal' => Role::whereIn('name', ['administrador', 'secretaria', 'tecnico'])->get(),
    
            // Rol único para beneficiario
            'roleUsuario' => Role::where('name', 'usuario')->first(),
        ]);
    }
// app/Http/Controllers/UserController.php

    public function store(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:beneficiario,personal',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role_id' => 'required|exists:roles,id',
            'beneficiario_id' => 'nullable|exists:beneficiarios,id',
        ]);

        // ✅ Solo si es beneficiario, verifica el límite
        if ($request->tipo === 'beneficiario') {
            $beneficiario_id = $request->beneficiario_id;

            // ✅ Cuenta cuántos usuarios YA existen para este beneficiario
            $count = User::where('beneficiario_id', $beneficiario_id)->count();

            if ($count >= 2) {
                return back()->withErrors([
                    'beneficiario_id' => 'Este beneficiario ya tiene 2 usuarios asignados.'
                ])->withInput();
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'beneficiario_id' => $request->beneficiario_id,
        ]);

        $role = Role::find($request->role_id);
        $user->assignRole($role);

        return redirect()->route('users.index')->with('success', '✅ Usuario creado correctamente.');
    }
    public function edit(User $user)
    {
        // Carga el beneficiario junto con el usuario
        $user->load('beneficiario');

        $beneficiarios = Beneficiario::all()->map(fn($b) => [
            'id' => $b->id,
            'nombre_completo' => $b->nombre_completo
        ]);

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'beneficiarios' => $beneficiarios
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email,' . $user->id,
            'beneficiario_id' => 'nullable|exists:beneficiarios,id',
        ]);

        $user->update([
            'name' => $request->name ?? optional(Beneficiario::find($request->beneficiario_id))->nombre_completo,
            'email' => $request->email,
            'beneficiario_id' => $request->beneficiario_id
        ]);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado.');
    }

    public function destroy(User $user)
    {
        $user->delete(); // o $user->forceDelete() si quieres eliminar permanentemente
    
        return redirect()->route('users.index')->with('success', 'Usuario eliminado y desvinculado.');
    }
}