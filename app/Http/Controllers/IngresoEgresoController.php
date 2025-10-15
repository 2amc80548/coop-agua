<?php

namespace App\Http\Controllers;

use App\Models\IngresoEgreso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class IngresoEgresoController extends Controller
{
    public function index(Request $request)
    {
        $query = IngresoEgreso::query();

        // Filtros opcionales
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', 'like', "%{$request->categoria}%");
        }

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        $registros = $query->with('usuarioRegistrado')->get();

        return Inertia::render('IngresosEgresos/Index', [  
            'registros' => $registros,
            'filters' => [
                'tipo' => $request->tipo,
                'categoria' => $request->categoria,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('IngresosEgresos/Create'); 
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:ingreso,egreso',
            'categoria' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'monto' => 'required|numeric|min:0',
            'fecha' => 'required|date',
        ]);

        $registro = IngresoEgreso::create([
            'tipo' => $request->tipo,
            'categoria' => $request->categoria,
            'descripcion' => $request->descripcion,
            'monto' => $request->monto,
            'fecha' => $request->fecha,
            'registrado_por' => Auth::id(),
        ]);

        return redirect()
            ->route('IngresosEgresos.index')
            ->with('success', 'Registro creado correctamente.');
    }

    public function show($id)
    {
        $registro = IngresoEgreso::with('usuarioRegistrado')->findOrFail($id);

        return Inertia::render('IngresosEgresos/Show', [  
            'registro' => $registro,
        ]);
    }

    public function edit($id)
    {
        $registro = IngresoEgreso::findOrFail($id);

        return Inertia::render('IngresosEgresos/Edit', [  
            'registro' => $registro,
        ]);
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
        ]);

        $registro->update([
            'tipo' => $request->tipo,
            'categoria' => $request->categoria,
            'descripcion' => $request->descripcion,
            'monto' => $request->monto,
            'fecha' => $request->fecha,
        ]);

        return redirect()
            ->route('IngresosEgresos.index')
            ->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy($id)
    {
        $registro = IngresoEgreso::findOrFail($id);
        $registro->delete();

        return redirect()
            ->route('IngresosEgresos.index')
            ->with('success', 'Registro eliminado correctamente.');
    }
}