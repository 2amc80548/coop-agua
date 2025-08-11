<?php

namespace App\Http\Controllers;

use App\Models\AccesoAutorizado;
use Illuminate\Http\Request;

class AccesoAutorizadoController extends Controller
{
    public function index()
    {
        return AccesoAutorizado::with('socio')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'socio_id' => 'required|exists:socios,id',
            'nombre' => 'required|string|max:255',
            'ci' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'relacion' => 'nullable|string|max:100',
        ]);

        $acceso = AccesoAutorizado::create($request->all());

        return response()->json([
            'message' => 'Acceso autorizado creado correctamente',
            'acceso' => $acceso->load('socio'),
        ], 201);
    }

    public function show($id)
    {
        $acceso = AccesoAutorizado::with('socio')->findOrFail($id);
        return response()->json($acceso);
    }

    public function update(Request $request, $id)
    {
        $acceso = AccesoAutorizado::findOrFail($id);

        $request->validate([
            'socio_id' => 'required|exists:socios,id',
            'nombre' => 'required|string|max:255',
            'ci' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'relacion' => 'nullable|string|max:100',
        ]);

        $acceso->update($request->all());

        return response()->json([
            'message' => 'Acceso autorizado actualizado correctamente',
            'acceso' => $acceso->load('socio'),
        ]);
    }

    public function destroy($id)
    {
        $acceso = AccesoAutorizado::findOrFail($id);
        $acceso->delete();

        return response()->json(['message' => 'Acceso autorizado eliminado correctamente']);
    }
}
