<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AfiliadoController extends Controller
{
    public function index()
    {
        return Inertia::render('Afiliados/Index', [
            // Si quieres mostrar si cumple requisitos, usa with('requisitos')
            'afiliados' => Afiliado::orderBy('nombre_completo')->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Afiliados/Create');
    }

    public function store(Request $request)
    {
        // 1) Validación de AFILIADO
        $validated = $request->validate([
            'nombre_completo'  => ['required','string','max:255'],
            'ci'               => ['required','string','max:50','unique:afiliados,ci'],
            'celular'          => ['nullable','string','max:20'],
            'direccion'        => ['required','string','max:255'],
            'tipo'             => ['required', Rule::in(['socio','usuario'])],
            'fecha_afiliacion' => ['nullable','date'],
            'fecha_baja'       => ['nullable','date','after_or_equal:fecha_afiliacion'],
            'codigo'           => ['required','string','max:50','unique:afiliados,codigo'],
            'barrio'           => ['nullable','string','max:100'],
            'tenencia'         => ['required', Rule::in(['propietario','compra_venta','posesion'])],
            'estado'           => ['required', Rule::in(['activo','suspendido','baja'])],
        ]);

        // 2) Validación CONDICIONAL de REQUISITOS (vienen en form.req.*)
        $tenencia = $request->input('tenencia');
        $reqRules = [
            'req.fotocopia_ci'      => ['boolean'],
            'req.plano_ubicacion'   => ['boolean'],
            'req.doc_compra_venta'  => ['boolean'],
            'req.croquis'           => ['boolean'],
            'req.certificacion_otb' => ['boolean'],
            'req.observaciones'     => ['nullable','string','max:1000'],
        ];
        if ($tenencia === 'propietario') {
            $reqRules['req.fotocopia_ci'][]    = 'accepted';
            $reqRules['req.plano_ubicacion'][] = 'accepted';
        }
        if ($tenencia === 'compra_venta') {
            $reqRules['req.fotocopia_ci'][]     = 'accepted';
            $reqRules['req.doc_compra_venta'][] = 'accepted';
        }
        if ($tenencia === 'posesion') {
            $reqRules['req.fotocopia_ci'][]       = 'accepted';
            $reqRules['req.croquis'][]            = 'accepted';
            $reqRules['req.certificacion_otb'][]  = 'accepted';
        }
        $request->validate($reqRules);

        // 3) Crear AFILIADO
        $afiliado = Afiliado::create($validated);

        // 4) Crear REQUISITOS (si vino "req")
        $req = $request->input('req', []);
        $afiliado->requisitos()->create([
            'escenario'          => $afiliado->tenencia,
            'fotocopia_ci'       => !empty($req['fotocopia_ci']),
            'plano_ubicacion'    => !empty($req['plano_ubicacion']),
            'doc_compra_venta'   => !empty($req['doc_compra_venta']),
            'croquis'            => !empty($req['croquis']),
            'certificacion_otb'  => !empty($req['certificacion_otb']),
            'observaciones'      => $req['observaciones'] ?? null,
            'registrado_por'     => auth()->id(),
        ]);

        return redirect()->route('afiliados.index')
            ->with('success', '✅ Afiliado creado correctamente.');
    }

    public function edit(Afiliado $afiliado)
    {
        // enviamos requisitos para precargar checkboxes
        $afiliado->load('requisitos');

        return Inertia::render('Afiliados/Edit', [
            'afiliado' => $afiliado
        ]);
    }

    public function update(Request $request, Afiliado $afiliado)
    {
        // 1) Validación AFILIADO
        $validated = $request->validate([
            'nombre_completo'  => ['required','string','max:255'],
            'ci'               => ['required','string','max:50', Rule::unique('afiliados','ci')->ignore($afiliado->id)],
            'celular'          => ['nullable','string','max:20'],
            'direccion'        => ['required','string','max:255'],
            'tipo'             => ['required', Rule::in(['socio','usuario'])],
            'fecha_afiliacion' => ['nullable','date'],
            'fecha_baja'       => ['nullable','date','after_or_equal:fecha_afiliacion'],
            'codigo'           => ['required','string','max:50', Rule::unique('afiliados','codigo')->ignore($afiliado->id)],
            'barrio'           => ['nullable','string','max:100'],
            'tenencia'         => ['required', Rule::in(['propietario','compra_venta','posesion'])],
            'estado'           => ['required', Rule::in(['activo','suspendido','baja'])],
        ]);

        // 2) Validación CONDICIONAL REQUISITOS
        $tenencia = $request->input('tenencia');
        $reqRules = [
            'req.fotocopia_ci'      => ['boolean'],
            'req.plano_ubicacion'   => ['boolean'],
            'req.doc_compra_venta'  => ['boolean'],
            'req.croquis'           => ['boolean'],
            'req.certificacion_otb' => ['boolean'],
            'req.observaciones'     => ['nullable','string','max:1000'],
        ];
        if ($tenencia === 'propietario') {
            $reqRules['req.fotocopia_ci'][]    = 'accepted';
            $reqRules['req.plano_ubicacion'][] = 'accepted';
        }
        if ($tenencia === 'compra_venta') {
            $reqRules['req.fotocopia_ci'][]     = 'accepted';
            $reqRules['req.doc_compra_venta'][] = 'accepted';
        }
        if ($tenencia === 'posesion') {
            $reqRules['req.fotocopia_ci'][]       = 'accepted';
            $reqRules['req.croquis'][]            = 'accepted';
            $reqRules['req.certificacion_otb'][]  = 'accepted';
        }
        $request->validate($reqRules);

        // 3) Actualizar AFILIADO
        $afiliado->update($validated);

        // 4) Crear/Actualizar REQUISITOS
        $req = $request->input('req', []);
        $afiliado->requisitos()->updateOrCreate(
            ['afiliado_id' => $afiliado->id],
            [
                'escenario'          => $afiliado->tenencia,
                'fotocopia_ci'       => !empty($req['fotocopia_ci']),
                'plano_ubicacion'    => !empty($req['plano_ubicacion']),
                'doc_compra_venta'   => !empty($req['doc_compra_venta']),
                'croquis'            => !empty($req['croquis']),
                'certificacion_otb'  => !empty($req['certificacion_otb']),
                'observaciones'      => $req['observaciones'] ?? null,
                'registrado_por'     => auth()->id(),
            ]
        );

        return redirect()->route('afiliados.index')
            ->with('success', '✅ Afiliado actualizado.');
    }

    public function destroy(Afiliado $afiliado)
    {
        $afiliado->delete();

        return redirect()->route('afiliados.index')
            ->with('success', '🗑️ Afiliado eliminado.');
    }

    // API simple
    public function buscarPorCI($ci)
    {
        $afiliado = Afiliado::where('ci', $ci)->first();

        if (!$afiliado) {
            return response()->json(null, 404);
        }

        return response()->json([
            'id'              => $afiliado->id,
            'nombre_completo' => $afiliado->nombre_completo,
        ]);
    }
}
