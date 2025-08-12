<?php

namespace App\Http\Controllers;

use App\Models\Beneficiario;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BeneficiarioController extends Controller
{
    /**
     * Listar todos los beneficiarios.
     */
    public function index()
    {
        return Inertia::render('Beneficiarios/Index', [
            'beneficiarios' => Beneficiario::all()
        ]);
    }

    /**
     * Mostrar formulario de creación.
     */
    public function create()
    {
        return Inertia::render('Beneficiarios/Create');
    }

    /**
     * Almacenar un nuevo beneficiario.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'ci' => 'required|string|unique:beneficiarios,ci',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'required|string|max:255',
            'tipo' => 'required|in:socio,usuario', // puedes cambiar por 'miembro', 'afiliado', etc.
        ]);

        Beneficiario::create($validated);

        return redirect()->route('beneficiarios.index')->with('success', '✅ Beneficiario creado correctamente.');
    }

    /**
     * Mostrar formulario de edición.
     */
    public function edit(Beneficiario $beneficiario)
    {
        return Inertia::render('Beneficiarios/Edit', [
            'beneficiario' => $beneficiario
        ]);
    }

    /**
     * Actualizar beneficiario.
     */
    public function update(Request $request, Beneficiario $beneficiario)
    {
        $validated = $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'ci' => 'required|string|unique:beneficiarios,ci,' . $beneficiario->id,
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'required|string|max:255',
            'tipo' => 'required|in:socio,usuario',
        ]);

        $beneficiario->update($validated);

        return redirect()->route('beneficiarios.index')->with('success', '✅ Beneficiario actualizado.');
    }

    /**
     * Eliminar beneficiario.
     */
    public function destroy(Beneficiario $beneficiario)
    {
        $beneficiario->delete();

        return redirect()->route('beneficiarios.index')->with('success', '🗑️ Beneficiario eliminado.');
    }

    public function buscarPorCI($ci)
{
    $beneficiario = Beneficiario::where('ci', $ci)->first();

    if (!$beneficiario) {
        return response()->json(null, 404);
    }

    return response()->json([
        'id' => $beneficiario->id,
        'nombre_completo' => $beneficiario->nombre_completo
    ]);
}

}