<?php

namespace App\Http\Controllers;

use App\Models\Beneficiario;
use App\Models\Conexion;
use Illuminate\Http\Request;
use Inertia\Inertia;


class ConexionController extends Controller
{
    // ✅ Listar conexiones con beneficiario
    public function index()
    {   
        return Inertia::render('Conexiones/Index', [
            'conexiones' => Conexion::with('beneficiario')->get()
        ]);
    }

    // ✅ Mostrar formulario de creación
    public function create()
    {
        $beneficiarios = Beneficiario::all()->map(function ($b) {
            return [
                'id' => $b->id,
                'nombre_completo' => $b->nombre_completo ?? $b->nombre . ' ' . $b->apellido,
                'ci' => $b->ci ?? null,
            ];
        });

        return Inertia::render('Conexiones/Create', [
            'beneficiarios' => $beneficiarios,
        ]);
    }

    // ✅ Mostrar formulario de edición
    public function edit(Conexion $conexion)
    {
        $conexion->load('beneficiario');

        $beneficiarios = Beneficiario::all()->map(function ($b) {
            return [
                'id' => $b->id,
                'nombre_completo' => $b->nombre_completo ?? $b->nombre . ' ' . $b->apellido,
                'ci' => $b->ci ?? null,
            ];
        });

        return Inertia::render('Conexiones/Edit', [
            'conexion' => $conexion,
            'beneficiarios' => $beneficiarios,
        ]);
    }

    // ✅ Mostrar detalle
    public function show(Conexion $conexion)
    {
        $conexion->load([
            'beneficiario',
            'lecturas',
            'facturas.pagos'
        ]);

        return Inertia::render('Conexiones/Show', [
            'conexion' => $conexion,
        ]);
    }

    // ✅ Crear conexión
    public function store(Request $request)
    {
        $request->validate([
            'codigo_medidor' => 'required|string|unique:conexiones,codigo_medidor|max:50',
            'beneficiario_id' => 'required|exists:beneficiarios,id',
            'estado' => 'required|in:activo,suspendido,eliminado',
            'direccion' => 'required|string|max:255',
            'zona' => 'nullable|string|max:100',
            'fecha_instalacion' => 'required|date',
            'tipo_conexion' => 'required|in:domiciliaria,comercial,institucional,otro',
        ]);

        $conexion = Conexion::create($request->all());

        return redirect()->route('conexiones.index')->with('success', '✅ Conexión creada correctamente.');
    }

    // ✅ Actualizar conexión
    public function update(Request $request, Conexion $conexion)
    {
        $request->validate([
            'codigo_medidor' => 'required|string|max:50|unique:conexiones,codigo_medidor,' . $conexion->id,
            'beneficiario_id' => 'required|exists:beneficiarios,id',
            'estado' => 'required|in:activo,suspendido,eliminado',
            'direccion' => 'required|string|max:255',
            'zona' => 'nullable|string|max:100',
            'fecha_instalacion' => 'required|date',
            'tipo_conexion' => 'required|in:domiciliaria,comercial,institucional,otro',
        ]);

        $conexion->update($request->all());

        return redirect()->route('conexiones.index')->with('success', '✅ Conexión actualizada correctamente.');
    }

    // ✅ Eliminar conexión
    public function destroy(Conexion $conexion)
    {
        $conexion->delete();

        return redirect()->route('conexiones.index')->with('success', '✅ Conexión eliminada correctamente.');
    }
}