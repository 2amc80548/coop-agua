<?php

namespace App\Http\Controllers;

use App\Models\IngresoEgreso;
use Illuminate\Http\Request;

class IngresoEgresoController extends Controller
{
    public function index(Request $request)
    {
        $query = IngresoEgreso::query();

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', 'like', "%{$request->categoria}%");
        }

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:ingreso,egreso',
            'categoria' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'monto' => 'required|numeric|min:0',
            'fecha' => 'required|date',
            'registrado_por' => 'required|exists:users,id',
        ]);

        $registro = IngresoEgreso::create($request->all());

        return response()->json([
            'message' => 'Registro creado correctamente',
            'registro' => $registro,
        ], 201);
    }

    public function show($id)
    {
        $registro = IngresoEgreso::findOrFail($id);
        return response()->json($registro);
    }

    public function update(Request $request, $id)
    {
        $registro = IngresoEgreso::findOrFail($id);

        $request->validate([
            'tipo' => 'required|in:ingreso,egreso',
            'categoria' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'monto' => 'required|numeric|min:0',
            'fecha' => 'required|date',
            'registrado_por' => 'required|exists:users,id',
        ]);

        $registro->update($request->all());

        return response()->json([
            'message' => 'Registro actualizado correctamente',
            'registro' => $registro,
        ]);
    }

    public function destroy($id)
    {
        $registro = IngresoEgreso::findOrFail($id);
        $registro->delete();

        return response()->json(['message' => 'Registro eliminado correctamente']);
    }
}
