<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Conexion;
use Illuminate\Http\Request;

class FacturaController extends Controller
{
    public function index(Request $request)
    {
        // Permite filtrar por fecha y estado
        $query = Factura::with('conexion.socio');

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha_emision', [$request->fecha_inicio, $request->fecha_fin]);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'conexion_id' => 'required|exists:conexiones,id',
            'fecha_emision' => 'required|date',
            'fecha_vencimiento' => 'required|date|after_or_equal:fecha_emision',
            'monto_total' => 'required|numeric|min:0',
            'estado' => 'required|in:pendiente,pagada,anulada',
        ]);

        $factura = Factura::create($request->all());

        return response()->json([
            'message' => 'Factura creada correctamente',
            'factura' => $factura->load('conexion.socio')
        ], 201);
    }

    public function show($id)
    {
        $factura = Factura::with(['conexion.socio', 'pagos'])->findOrFail($id);
        return response()->json($factura);
    }

    public function update(Request $request, $id)
    {
        $factura = Factura::findOrFail($id);

        $request->validate([
            'conexion_id' => 'required|exists:conexiones,id',
            'fecha_emision' => 'required|date',
            'fecha_vencimiento' => 'required|date|after_or_equal:fecha_emision',
            'monto_total' => 'required|numeric|min:0',
            'estado' => 'required|in:pendiente,pagada,anulada',
        ]);

        $factura->update($request->all());

        return response()->json([
            'message' => 'Factura actualizada correctamente',
            'factura' => $factura->load('conexion.socio')
        ]);
    }

    public function destroy($id)
    {
        $factura = Factura::findOrFail($id);
        $factura->delete();

        return response()->json(['message' => 'Factura eliminada correctamente']);
    }
}
