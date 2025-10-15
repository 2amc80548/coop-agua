<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Factura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;


class PagoController extends Controller
{
    /**
     * Mostrar lista de pagos con filtros
     */
    public function index(Request $request)
    {
        $query = Pago::with('factura.conexion.beneficiario', 'usuarioRegistrado');

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha_pago', [$request->fecha_inicio, $request->fecha_fin]);
        }

        if ($request->filled('factura_id')) {
            $query->where('factura_id', $request->factura_id);
        }

        $pagos = $query->get();

        return Inertia::render('Pagos/Index', [
            'pagos' => $pagos,
            'filters' => $request->only('fecha_inicio', 'fecha_fin', 'factura_id'),
        ]);
    }

    /**
     * Mostrar formulario para crear pago
     */
    public function create(Request $request)
    {
        //  Validar que factura_id exista
        $request->validate([
            'factura_id' => 'required|exists:facturas,id',
        ]);

        $factura = Factura::with('conexion.beneficiario')->findOrFail($request->factura_id);

        return Inertia::render('Pagos/Create', [
            'factura' => $factura,
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * Guardar un nuevo pago
     */
    public function store(Request $request)
    {
        $request->validate([
            'factura_id' => 'required|exists:facturas,id',
            'monto_pagado' => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|string|max:50',
            'fecha_pago' => 'required|date',
        ]);

        $factura = Factura::findOrFail($request->factura_id);

        // Crear el pago
        $pago = Pago::create([
            'factura_id' => $request->factura_id,
            'monto_pagado' => $request->monto_pagado,
            'metodo_pago' => $request->metodo_pago,
            'fecha_pago' => $request->fecha_pago,
            'registrado_por' => Auth::id(),
        ]);

        // Recalcular estado de la factura
        $totalPagos = $factura->pagos()->sum('monto_pagado');
        if ($totalPagos >= $factura->monto_total) {
            $factura->estado = 'pagada';
        } else {
            $factura->estado = 'pendiente';
        }
        $factura->save();

        return redirect()
            ->route('pagos.index')
            ->with('success', 'Pago registrado correctamente.');
    }

    /**
     * Mostrar detalles de un pago
     */
    public function show($id)
    {
        $pago = Pago::with([
            'factura.conexion.beneficiario',
            'usuarioRegistrado'
        ])->findOrFail($id);

        return Inertia::render('Pagos/Show', [
            'pago' => $pago,
        ]);
    }

    /**
     * Mostrar formulario para editar pago
     */
    public function edit($id)
    {
        $pago = Pago::with('factura.conexion.beneficiario')->findOrFail($id);
        $facturas = Factura::with('conexion.beneficiario')->get(); // Todas las facturas

        return Inertia::render('Pagos/Edit', [
            'pago' => $pago,
            'facturas' => $facturas,
        ]);
    }

    /**
     * Actualizar un pago
     */
    public function update(Request $request, $id)
    {
        $pago = Pago::findOrFail($id);

        $request->validate([
            'factura_id' => 'required|exists:facturas,id',
            'monto_pagado' => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|string|max:50',
            'fecha_pago' => 'required|date',
        ]);

        $pago->update([
            'factura_id' => $request->factura_id,
            'monto_pagado' => $request->monto_pagado,
            'metodo_pago' => $request->metodo_pago,
            'fecha_pago' => $request->fecha_pago,
        ]);

        // Recalcular estado de la factura
        $factura = $pago->factura;
        $totalPagos = $factura->pagos()->sum('monto_pagado');
        if ($totalPagos >= $factura->monto_total) {
            $factura->estado = 'pagada';
        } else {
            $factura->estado = 'pendiente';
        }
        $factura->save();

        return redirect()
            ->route('pagos.index')
            ->with('success', 'Pago actualizado correctamente.');
    }

    /**
     * Eliminar un pago
     */
    public function destroy($id)
    {
        $pago = Pago::findOrFail($id);
        $factura = $pago->factura;
        $pago->delete();

        // Recalcular estado de la factura
        $totalPagos = $factura->pagos()->sum('monto_pagado');
        if ($totalPagos >= $factura->monto_total) {
            $factura->estado = 'pagada';
        } else {
            $factura->estado = 'pendiente';
        }
        $factura->save();

        return redirect()
            ->route('pagos.index')
            ->with('success', 'Pago eliminado correctamente.');
    }
}