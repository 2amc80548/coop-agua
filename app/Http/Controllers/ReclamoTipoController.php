<?php

namespace App\Http\Controllers;

use App\Models\ReclamoTipo;
use Illuminate\Http\Request;

class ReclamoTipoController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate(['nombre' => 'required|string|max:100|unique:reclamo_tipos,nombre']);
        ReclamoTipo::create($data);
        return back()->with('success', 'Tipo de reclamo creado.');
    }

    public function update(Request $request, ReclamoTipo $reclamoTipo)
    {
        $data = $request->validate(['nombre' => 'required|string|max:100|unique:reclamo_tipos,nombre,' . $reclamoTipo->id]);
        $reclamoTipo->update($data);
        return back()->with('success', 'Tipo de reclamo actualizado.');
    }

    public function destroy(ReclamoTipo $reclamoTipo)
    {
        $reclamoTipo->delete();
        return back()->with('success', 'Tipo de reclamo eliminado.');
    }
}
