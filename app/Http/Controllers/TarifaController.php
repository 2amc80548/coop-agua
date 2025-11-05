<?php

namespace App\Http\Controllers;

use App\Models\Tarifa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TarifaController extends Controller
{
    public function index()
    {
        return Inertia::render('Tarifas/Index', [
            'tarifas' => Tarifa::withCount('conceptos')->orderByDesc('vigente_desde')->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Tarifas/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vigente_desde' => ['required','date'],
            'vigente_hasta' => ['nullable','date','after_or_equal:vigente_desde'],
            'activo'        => ['required','boolean'],

            'min_m3'        => ['required','integer','min:0'],
            'min_monto'     => ['required','numeric','min:0'],
            'precio_m3'     => ['required','numeric','min:0'],

            'descuento_adulto_mayor_pct' => ['required','numeric','min:0','max:100'],

            'afiliacion_socio_monto'     => ['required','numeric','min:0'],
            'afiliacion_usuario_monto'   => ['required','numeric','min:0'],
            'multa_corte_monto'          => ['required','numeric','min:0'],
            'cisterna_10k_monto'         => ['required','numeric','min:0'],

            'notas' => ['nullable','string','max:255'],
        ]);

        if (!empty($data['activo'])) {
            Tarifa::where('activo', 1)->update(['activo' => 0]);
        }

        Tarifa::create($data);

        return redirect()->route('tarifas.index')->with('success', 'Tarifa creada.');
    }

    public function edit(Tarifa $tarifa)
    {
        $tarifa->load('conceptos');
        return Inertia::render('Tarifas/Edit', [
            'tarifa' => $tarifa
        ]);
    }

    public function update(Request $request, Tarifa $tarifa)
    {
        $data = $request->validate([
            'vigente_desde' => ['required','date'],
            'vigente_hasta' => ['nullable','date','after_or_equal:vigente_desde'],
            'activo'        => ['required','boolean'],

            'min_m3'        => ['required','integer','min:0'],
            'min_monto'     => ['required','numeric','min:0'],
            'precio_m3'     => ['required','numeric','min:0'],

            'descuento_adulto_mayor_pct' => ['required','numeric','min:0','max:100'],

            'afiliacion_socio_monto'     => ['required','numeric','min:0'],
            'afiliacion_usuario_monto'   => ['required','numeric','min:0'],
            'multa_corte_monto'          => ['required','numeric','min:0'],
            'cisterna_10k_monto'         => ['required','numeric','min:0'],

            'notas' => ['nullable','string','max:255'],
        ]);

        if (!empty($data['activo'])) {
            Tarifa::where('id', '!=', $tarifa->id)->where('activo', 1)->update(['activo' => 0]);
        }

        $tarifa->update($data);

        return redirect()->route('tarifas.index')->with('success', 'Tarifa actualizada.');
    }

    public function destroy(Tarifa $tarifa)
    {
        if (Tarifa::count() <= 1) {
            return back()->withErrors('No puedes eliminar la única tarifa.');
        }
        $tarifa->delete();
        return redirect()->route('tarifas.index')->with('success', 'Tarifa eliminada.');
    }
}
