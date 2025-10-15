<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\Conexion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConexionController extends Controller
{
    public function index()
    {
        return Inertia::render('Conexiones/Index', [
            'conexiones' => Conexion::with('afiliado')->get()
        ]);
    }

    public function create()
    {
        $afiliados = Afiliado::all()->map(function ($a) {
            return [
                'id' => $a->id,
                'nombre_completo' => $a->nombre_completo,
                'ci' => $a->ci,
            ];
        });

        return Inertia::render('Conexiones/Create', [
            'afiliados' => $afiliados,
        ]);
    }

    public function edit(Conexion $conexion)
    {
        $conexion->load('afiliado');

        $afiliados = Afiliado::all()->map(function ($a) {
            return [
                'id' => $a->id,
                'nombre_completo' => $a->nombre_completo,
                'ci' => $a->ci,
            ];
        });

        return Inertia::render('Conexiones/Edit', [
            'conexion'  => $conexion,
            'afiliados' => $afiliados,
        ]);
    }

    public function show(Conexion $conexion)
    {
        $conexion->load(['afiliado','lecturas','facturas.pagos']);

        return Inertia::render('Conexiones/Show', [
            'conexion' => $conexion,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo_medidor'   => 'required|string|unique:conexiones,codigo_medidor|max:50',
            'afiliado_id'      => 'required|exists:afiliados,id',
            'estado'           => 'required|in:activo,suspendido,eliminado',
            'direccion'        => 'required|string|max:255',
            'zona'             => 'nullable|string|max:100',
            'fecha_instalacion'=> 'required|date',
            'tipo_conexion'    => 'required|in:domiciliaria,comercial,institucional,otro',
        ]);

        $conexion = Conexion::create($request->all());

        return redirect()->route('conexiones.index')->with('success', '✅ Conexión creada correctamente.');
    }

    public function update(Request $request, Conexion $conexion)
    {
        $request->validate([
            'codigo_medidor'   => 'required|string|max:50|unique:conexiones,codigo_medidor,' . $conexion->id,
            'afiliado_id'      => 'required|exists:afiliados,id',
            'estado'           => 'required|in:activo,suspendido,eliminado',
            'direccion'        => 'required|string|max:255',
            'zona'             => 'nullable|string|max:100',
            'fecha_instalacion'=> 'required|date',
            'tipo_conexion'    => 'required|in:domiciliaria,comercial,institucional,otro',
        ]);

        $conexion->update($request->all());

        return redirect()->route('conexiones.index')->with('success', '✅ Conexión actualizada correctamente.');
    }

    public function destroy(Conexion $conexion)
    {
        $conexion->delete();

        return redirect()->route('conexiones.index')->with('success', '✅ Conexión eliminada correctamente.');
    }
}
