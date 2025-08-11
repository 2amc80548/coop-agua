<?php

namespace App\Http\Controllers;

use App\Models\Conexion;
use App\Models\Socio;
use Illuminate\Http\Request;
use App\Models\Lectura;
use App\Models\Factura;
use App\Models\Pago;



class ConexionController extends Controller
{
    // Listar todas las conexiones con datos del socio
    public function index()
    {
        return Conexion::with('socio')->get();
    }

    // Crear una nueva conexión
    public function store(Request $request)
    {
        $request->validate([
            'codigo_medidor' => 'required|string|unique:conexiones,codigo_medidor|max:50',
            'socio_id' => 'required|exists:socios,id',
            'estado' => 'required|in:activo,suspendido,eliminado',
            'direccion' => 'required|string|max:255',
            'zona' => 'nullable|string|max:100',
        ]);

        $conexion = Conexion::create($request->all());

        return response()->json([
            'message' => 'Conexión creada correctamente',
            'conexion' => $conexion->load('socio')
        ], 201);
    }
    public function show($id)
    {
        $conexion = Conexion::with([
            'socio',
            'lecturas',
            'facturas.pagos'
        ])->findOrFail($id);

        return response()->json($conexion);
    }

    public function update(Request $request, $id)
    {
        $conexion = Conexion::findOrFail($id);

        $request->validate([
            'codigo_medidor' => 'required|string|max:50|unique:conexiones,codigo_medidor,' . $conexion->id,
            'socio_id' => 'required|exists:socios,id',
            'estado' => 'required|in:activo,suspendido,eliminado',
            'direccion' => 'required|string|max:255',
            'zona' => 'nullable|string|max:100',
        ]);

        $conexion->update($request->all());

        return response()->json([
            'message' => 'Conexión actualizada correctamente',
            'conexion' => $conexion->load('socio')
        ]);
    }

    public function destroy($id)
    {
        $conexion = Conexion::findOrFail($id);
        $conexion->delete();

        return response()->json([
            'message' => 'Conexión eliminada correctamente'
        ]);
    }


}
