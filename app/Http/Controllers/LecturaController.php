<?php

namespace App\Http\Controllers;

use App\Models\Lectura;
use App\Models\Conexion;
use Illuminate\Http\Request;

class LecturaController extends Controller
{
    // Listar todas las lecturas con datos de la conexión
    public function index()
    {
        return Lectura::with('conexion')->get();
    }

    // Crear una nueva lectura
    public function store(Request $request)
    {
        $request->validate([
            'conexion_id' => 'required|exists:conexiones,id',
            'fecha_lectura' => 'required|date',
            'lectura_anterior' => 'required|integer|min:0',
            'lectura_actual' => 'required|integer|min:0|gte:lectura_anterior',
            'observacion' => 'nullable|string|max:255',
            'registrado_por' => 'required|exists:users,id',
        ]);

        $lectura = Lectura::create($request->all());

        return response()->json([
            'message' => 'Lectura creada correctamente',
            'lectura' => $lectura->load('conexion')
        ], 201);
    }

    // Mostrar lectura específica con relación a conexión
    public function show($id)
    {
        $lectura = Lectura::with('conexion')->findOrFail($id);
        return response()->json($lectura);
    }

    // Actualizar lectura
    public function update(Request $request, $id)
    {
        $lectura = Lectura::findOrFail($id);

        $request->validate([
            'conexion_id' => 'required|exists:conexiones,id',
            'fecha_lectura' => 'required|date',
            'lectura_anterior' => 'required|integer|min:0',
            'lectura_actual' => 'required|integer|min:0|gte:lectura_anterior',
            'observacion' => 'nullable|string|max:255',
            'registrado_por' => 'required|exists:users,id',
        ]);

        $lectura->update($request->all());

        return response()->json([
            'message' => 'Lectura actualizada correctamente',
            'lectura' => $lectura->load('conexion')
        ]);
    }

    // Eliminar lectura
    public function destroy($id)
    {
        $lectura = Lectura::findOrFail($id);
        $lectura->delete();

        return response()->json([
            'message' => 'Lectura eliminada correctamente'
        ]);
    }
}
