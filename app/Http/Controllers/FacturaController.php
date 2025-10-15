<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Conexion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FacturaController extends Controller
{
    /**
     * Mostrar lista de facturas con filtros
     */
    public function index(Request $request)
{
    $query = Factura::with('conexion.beneficiario');

    if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
        $query->whereBetween('fecha_emision', [$request->fecha_inicio, $request->fecha_fin]);
    }

    if ($request->filled('estado')) {
        $query->where('estado', $request->estado);
    }

    $facturas = $query->get()->map(function ($factura) {
        // Convertir campos decimales a float
        $factura->monto_total = (float) $factura->monto_total;
        $factura->consumo_m3 = $factura->consumo_m3 ? (float) $factura->consumo_m3 : 0;
        return $factura;
    });

    return Inertia::render('Facturas/Index', [
        'facturas' => $facturas,
        'filters' => $request->only('fecha_inicio', 'fecha_fin', 'estado'),
    ]);
}
    /**
     * Mostrar formulario para crear factura
     */
    // public function create()
    // {
    //     $conexiones = Conexion::with('beneficiario')->get();

    //     return Inertia::render('Facturas/Create', [
    //         'conexiones' => $conexiones,
    //     ]);
    // }

    // /**
    //  * Guardar una nueva factura
    //  */
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'conexion_id' => 'required|exists:conexiones,id',
    //         'fecha_emision' => 'required|date',
    //         'monto_total' => 'required|numeric|min:0',
    //         'estado' => 'required|in:pendiente,pagada,anulada',
    //     ]);

    //     Factura::create($request->all());

    //     return redirect()
    //         ->route('facturas.index')
    //         ->with('success', 'Factura creada correctamente.');
    // }

    /**
     * Mostrar detalles de una factura
     */
    public function show($id)
    {
        $factura = Factura::with([
            'conexion.beneficiario',
            'pagos'
        ])->findOrFail($id);

        return Inertia::render('Facturas/Show', [
            'factura' => $factura,
        ]);
    }

    /**
     * Mostrar formulario para editar
     */
    public function edit($id)
    {
        $factura = Factura::with('conexion.beneficiario')->findOrFail($id);
        $conexiones = Conexion::with('beneficiario')->get();

        return Inertia::render('Facturas/Edit', [
            'factura' => $factura,
            'conexiones' => $conexiones,
        ]);
    }

    /**
     * Actualizar una factura
     */
    public function update(Request $request, $id)
    {
        $factura = Factura::findOrFail($id);

        $request->validate([
            'conexion_id' => 'required|exists:conexiones,id',
            'fecha_emision' => 'required|date',
            'monto_total' => 'required|numeric|min:0',
            'estado' => 'required|in:pendiente,pagada,anulada',
        ]);

        $factura->update($request->all());

        return redirect()
            ->route('facturas.index')
            ->with('success', 'Factura actualizada correctamente.');
    }

    /**
     * Eliminar una factura
     */
    public function destroy($id)
    {
        $factura = Factura::findOrFail($id);
        $factura->delete();

        return redirect()
            ->route('facturas.index')
            ->with('success', 'Factura eliminada correctamente.');
    }
}