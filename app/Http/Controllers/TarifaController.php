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
            'tipo_conexion' => ['required','in:domiciliaria,comercial,institucional,otro'],

            'min_m3'                   => ['required','integer','min:0'],
            'min_monto'                => ['required','numeric','min:0'],
            'precio_m3'                => ['required','numeric','min:0'],
            'bloque2_limite_m3'        => ['nullable','integer','min:0'],
            'bloque2_precio_m3'        => ['nullable','numeric','min:0'],
            'bloque3_precio_m3'        => ['nullable','numeric','min:0'],
            'descuento_ley_m3_limite'  => ['nullable','integer','min:0'],
            'cargo_fijo_mantenimiento' => ['nullable','numeric','min:0'],

            'descuento_adulto_mayor_pct' => ['required','numeric','min:0','max:100'],

            'afiliacion_socio_monto'     => ['required','numeric','min:0'],
            'afiliacion_usuario_monto'   => ['required','numeric','min:0'],
            'multa_corte_monto'          => ['required','numeric','min:0'],
            'cisterna_10k_monto'         => ['required','numeric','min:0'],

            'notas' => ['nullable','string','max:255'],
        ]);

        if (!empty($data['activo'])) {
            Tarifa::where('activo', 1)
            ->where('tipo_conexion', $data['tipo_conexion'])
            ->update(['activo' => 0]);
        }

        Tarifa::create($data);

        return redirect()->route('tarifas.index')->with('success', 'Tarifa creada con éxito.');
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
            'tipo_conexion' => ['required','in:domiciliaria,comercial,institucional,otro'],

            'min_m3'                   => ['required','integer','min:0'],
            'min_monto'                => ['required','numeric','min:0'],
            'precio_m3'                => ['required','numeric','min:0'],
            'bloque2_limite_m3'        => ['nullable','integer','min:0'],
            'bloque2_precio_m3'        => ['nullable','numeric','min:0'],
            'bloque3_precio_m3'        => ['nullable','numeric','min:0'],
            'descuento_ley_m3_limite'  => ['nullable','integer','min:0'],
            'cargo_fijo_mantenimiento' => ['nullable','numeric','min:0'],

            'descuento_adulto_mayor_pct' => ['required','numeric','min:0','max:100'],

            'afiliacion_socio_monto'     => ['required','numeric','min:0'],
            'afiliacion_usuario_monto'   => ['required','numeric','min:0'],
            'multa_corte_monto'          => ['required','numeric','min:0'],
            'cisterna_10k_monto'         => ['required','numeric','min:0'],

            'notas' => ['nullable','string','max:255'],
        ]);

        if (!empty($data['activo'])) {
            Tarifa::where('id', '!=', $tarifa->id)
            ->where('activo', 1)
            ->where('tipo_conexion', $data['tipo_conexion'])
            ->update(['activo' => 0]);
        }

        $tarifa->update($data);

        return redirect()->route('tarifas.index')->with('success', 'Tarifa actualizada correctamente.');
    }

    /**
     * API: Simulador de tarifas dinámicas en vivo.
     */
    public function simular(Request $request, \App\Services\TarifaCalculatorService $calculator)
    {
        $validated = $request->validate([
            'consumo' => 'required|numeric|min:0',
            'tarifa_id' => 'nullable|exists:tarifas,id',
            'tipo_conexion' => 'nullable|string',
            'adulto_mayor' => 'nullable|boolean',
        ]);

        $tarifa = null;
        if (!empty($validated['tarifa_id'])) {
            $tarifa = Tarifa::find($validated['tarifa_id']);
        }
        if (!$tarifa && !empty($validated['tipo_conexion'])) {
            $tarifa = $calculator->getTarifaActiva($validated['tipo_conexion']);
        }
        if (!$tarifa) {
            $tarifa = Tarifa::where('activo', 1)->first();
        }

        if (!$tarifa) {
            return response()->json(['error' => 'No se encontró tarifa activa para simular.'], 404);
        }

        $dummyConexion = new \App\Models\Conexion();
        $dummyConexion->tipo_conexion = $tarifa->tipo_conexion;
        
        $dummyAfiliado = new \App\Models\Afiliado();
        $dummyAfiliado->adulto_mayor = (bool) ($validated['adulto_mayor'] ?? false);
        $dummyConexion->setRelation('afiliado', $dummyAfiliado);

        $dummyLectura = new \App\Models\Lectura();
        $dummyLectura->lectura_anterior = 0;
        $dummyLectura->lectura_actual = (float) $validated['consumo'];
        $dummyLectura->setRelation('conexion', $dummyConexion);

        $resultado = $calculator->calcular($dummyLectura, $tarifa);

        return response()->json($resultado);
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
