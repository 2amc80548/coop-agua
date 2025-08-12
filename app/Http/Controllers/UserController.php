<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Beneficiario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia; // 

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('beneficiario')->get();
        return Inertia::render('Users/Index', [
            'users' => $users
        ]);
    }

    public function create()
    {
        $beneficiarios = Beneficiario::all()->map(fn($b) => [
            'id' => $b->id,
            'nombre_completo' => $b->nombre_completo
        ]);

        return Inertia::render('Users/Create', [
            'beneficiarios' => $beneficiarios
        ]);
    }

    public function store(Request $request)
{
    $request->validate([
        'email' => 'required|email|unique:users',
        'password' => 'required|min:6',
        'beneficiario_id' => [
            'required',
            'exists:beneficiarios,id',
            // Evitar que un beneficiario tenga más de un usuario
           
        ],
    ]);

    User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'beneficiario_id' => $request->beneficiario_id,
    ]);

    return redirect()->route('users.index')->with('success', '✅ Usuario asignado al beneficiario correctamente.');
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
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Usuario eliminado.');
    }
}