<?php

namespace App\Http\Controllers;

use App\Models\Beneficiario as Socio;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SocioController extends Controller
{
    // Listar todos los socios


    
    public function index()
    {
        return Inertia::render('Socios/Index', [
            'socios' => Socio::with('conexiones')->get()
        ]);
    }

    // Guardar un nuevo socio
    public function store(Request $request)
    {
        $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'ci' => 'required|string|unique:socios,ci',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'tipo' => 'required|in:socio,usuario',
        ]);

        return Socio::create($request->all());
    }

    // Mostrar un socio específico
    public function show(Socio $socio)
    {
        return $socio->load('conexiones');
    }

    // Actualizar socio
    public function update(Request $request, Socio $socio)
    {
        $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'ci' => 'required|string|unique:socios,ci,' . $socio->id,
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id',
             'tipo' => 'required|in:socio,usuario',

        ]);

        $socio->update($request->all());
        return $socio;
    }

    // Eliminar socio
    public function destroy(Socio $socio)
    {
        $socio->delete();
        return response()->json(['message' => 'Socio eliminado']);
    }
}
