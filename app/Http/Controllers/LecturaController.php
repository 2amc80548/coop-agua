<?php

namespace App\Http\Controllers;

use App\Models\Lectura;
use App\Models\Conexion;
use App\Models\Factura; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Beneficiario;


class LecturaController extends Controller
{
    public function index()
    {
        $lecturas = Lectura::with(['conexion.beneficiario', 'usuarioRegistrado'])->get();

        return Inertia::render('Lecturas/Index', [
            'lecturas' => $lecturas,
        ]);
    }

    public function create()
{
    $conexiones = Conexion::with('beneficiario')->get()->map(function ($conexion) {
        // 🔍 Obtener la última lectura REAL por fecha_lectura DESC
        $ultimaLectura = Lectura::where('conexion_id', $conexion->id)
            ->orderBy('fecha_lectura', 'desc')
            ->orderBy('id', 'desc') // ⚠️ Si hay fechas iguales, toma la más reciente por ID
            ->first();

        return [
            'id' => $conexion->id,
            'codigo_medidor' => $conexion->codigo_medidor,
            'direccion' => $conexion->direccion,
            'beneficiario' => $conexion->beneficiario ? [
                'nombre_completo' => $conexion->beneficiario->nombre_completo,
            ] : null,
            'lectura_anterior' => $ultimaLectura ? $ultimaLectura->lectura_actual : 0,
        ];
    });

    return Inertia::render('Lecturas/Create', [
        'conexiones' => $conexiones->toArray(),
    ]);
}

    public function store(Request $request)
    {
        $request->validate([
            'conexion_id' => 'required|exists:conexiones,id',
            'fecha_lectura' => 'required|date',
            'lectura_anterior' => 'required|numeric|min:0',   
            'lectura_actual' => 'required|numeric|min:0|gte:lectura_anterior',
            'observacion' => 'nullable|string|max:255',
        ]);

        $lectura = Lectura::create([
            'conexion_id' => $request->conexion_id,
            'fecha_lectura' => $request->fecha_lectura,
            'lectura_anterior' => $request->lectura_anterior, 
            'lectura_actual' => $request->lectura_actual,
            'observacion' => $request->observacion,
            'registrado_por' => Auth::id(),
        ]);

        // ✅ Generar factura automáticamente
        $consumo = $lectura->lectura_actual - $lectura->lectura_anterior;
        $precioPorM3 = env('PRECIO_POR_METRO_CUBICO', 2.50);
        $montoTotal = $consumo * $precioPorM3;

        Factura::create([
            'conexion_id' => $lectura->conexion_id,
            'lectura_id' => $lectura->id,
            'consumo_m3' => $consumo,
            'monto_total' => $montoTotal,
            'estado' => 'pendiente',
            'fecha_emision' => now()->format('Y-m-d'),
        ]);
       
        return redirect()
            ->route('lecturas.index')
            ->with('success', 'Lectura y factura creadas correctamente.');
    }

    public function show($id)
    {
        $lectura = Lectura::with(['conexion.beneficiario', 'usuarioRegistrado'])->findOrFail($id);

        return Inertia::render('Lecturas/Show', [
            'lectura' => $lectura,
        ]);
    }

    public function edit($id)
    {
        $lectura = Lectura::with(['conexion.beneficiario', 'usuarioRegistrado'])->findOrFail($id);
        //$conexiones = Conexion::with('beneficiario')->get(); // solo si necesitas cambiar conexión (no recomendado)
    
        return Inertia::render('Lecturas/Edit', [
            'lectura' => $lectura,
            'conexiones' => $conexiones,
        ]);
    }

    public function update(Request $request, $id)
{
    $lectura = Lectura::findOrFail($id);

    $request->validate([
        'fecha_lectura' => 'required|date',
        'lectura_anterior' => 'required|numeric|min:0',
        'lectura_actual' => 'required|numeric|min:0|gte:lectura_anterior',
        'observacion' => 'nullable|string|max:255',
    ]);

    $lectura->update($request->only([
        'fecha_lectura',
        'lectura_anterior',
        'lectura_actual',
        'observacion'
    ]));

    return redirect()
        ->route('lecturas.index')
        ->with('success', 'Lectura actualizada correctamente.');
}
    public function destroy($id)
    {
        $lectura = Lectura::findOrFail($id);
        $lectura->delete();

        return redirect()->route('lecturas.index')->with('success', 'Lectura eliminada correctamente.');
    }
}