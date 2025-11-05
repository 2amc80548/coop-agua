<?php

namespace App\Http\Controllers;

use App\Models\Tarifa;
use App\Models\TarifaConcepto;
use Illuminate\Http\Request;

class TarifaConceptoController extends Controller
{
    public function store(Request $request, Tarifa $tarifa)
    {
        $data = $request->validate([
            'codigo'        => ['required','string','max:50'],
            'nombre'        => ['required','string','max:100'],
            'tipo'          => ['required','in:FIJO,PORCENTAJE'],
            'valor'         => ['required','numeric','min:0'],
            'aplica_sobre'  => ['required','in:consumo,subtotal,n/a'],
            'activo'        => ['required','boolean'],
        ]);

        // Evitar duplicados por (tarifa, codigo)
        if (TarifaConcepto::where('tarifa_id', $tarifa->id)->where('codigo', $data['codigo'])->exists()) {
            return back()->withErrors('Ya existe un concepto con ese código en esta tarifa.');
        }

        $data['tarifa_id'] = $tarifa->id;
        TarifaConcepto::create($data);

        return back()->with('success', 'Concepto agregado.');
    }

    public function update(Request $request, Tarifa $tarifa, TarifaConcepto $concepto)
    {
        if ($concepto->tarifa_id !== $tarifa->id) {
            abort(404);
        }

        $data = $request->validate([
            'nombre'        => ['required','string','max:100'],
            'tipo'          => ['required','in:FIJO,PORCENTAJE'],
            'valor'         => ['required','numeric','min:0'],
            'aplica_sobre'  => ['required','in:consumo,subtotal,n/a'],
            'activo'        => ['required','boolean'],
        ]);

        $concepto->update($data);

        return back()->with('success', 'Concepto actualizado.');
    }

    public function destroy(Tarifa $tarifa, TarifaConcepto $concepto)
    {
        if ($concepto->tarifa_id !== $tarifa->id) {
            abort(404);
        }

        $concepto->delete();
        return back()->with('success', 'Concepto eliminado.');
    }
}
