<?php

namespace App\Http\Controllers;

use App\Models\Zona;
use Illuminate\Http\Request;

class ZonaController extends Controller
{
    /**
     * Guarda una nueva zona desde el modal.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasRole(['Administrador', 'Secretaria'])) {
            abort(403, 'No autorizado');
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:zonas,nombre',
        ]);

        $zona = Zona::create($validated);

        
        return redirect()->back()
            ->with('success', '¡Zona creada!')
            ->with('nueva_zona', $zona); 
}
}