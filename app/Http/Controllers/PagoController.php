<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Factura;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PagoController extends Controller
{
    public function index(Request $request)
    {
        $query = Pago::with('factura.conexion.socio');

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha_pago', [$request->fecha_inicio, $request->fecha_fin]);
        }

        if ($request->filled('factura_id')) {
            $query->where('factura_id', $request->factura_id);
        }

        return $query->get();
        
    }

        
    public function store(Request $request)
    {
        $request->validate([
            'factura_id' => 'required|exists:facturas,id',
            'fecha_pago' => 'required|date',
            'monto_pagado' => 'required|numeric|min:0',
            'forma_pago' => 'required|string|max:50',
            'registrado_por' => 'required|exists:users,id',
        ]);

        $pago = Pago::create($request->all());

        // Actualizar estado de factura si está pagada completamente
        $factura = Factura::find($request->factura_id);
        $totalPagos = $factura->pagos()->sum('monto_pagado');
        if ($totalPagos >= $factura->monto_total) {
            $factura->estado = 'pagada';
            $factura->save();
        }

        return response()->json([
            'message' => 'Pago registrado correctamente',
            'pago' => $pago->load('factura.conexion.socio'),
        ], 201);
    }

    public function show($id)
    {
        $pago = Pago::with('factura.conexion.socio')->findOrFail($id);
        return response()->json($pago);
    }

    public function update(Request $request, $id)
    {
        $pago = Pago::findOrFail($id);

        $request->validate([
            'factura_id' => 'required|exists:facturas,id',
            'fecha_pago' => 'required|date',
            'monto_pagado' => 'required|numeric|min:0',
            'forma_pago' => 'required|string|max:50',
            'registrado_por' => 'required|exists:users,id',
        ]);

        $pago->update($request->all());

        // Recalcular estado factura
        $factura = Factura::find($request->factura_id);
        $totalPagos = $factura->pagos()->sum('monto_pagado');
        if ($totalPagos >= $factura->monto_total) {
            $factura->estado = 'pagada';
        } else {
            $factura->estado = 'pendiente';
        }
        $factura->save();

        return response()->json([
            'message' => 'Pago actualizado correctamente',
            'pago' => $pago->load('factura.conexion.socio'),
        ]);
    }

    public function destroy($id)
    {
        $pago = Pago::findOrFail($id);
        $factura = $pago->factura;
        $pago->delete();

        // Recalcular estado factura después de eliminar pago
        $totalPagos = $factura->pagos()->sum('monto_pagado');
        if ($totalPagos >= $factura->monto_total) {
            $factura->estado = 'pagada';
        } else {
            $factura->estado = 'pendiente';
        }
        $factura->save();

        return response()->json(['message' => 'Pago eliminado correctamente']);
    }
}
