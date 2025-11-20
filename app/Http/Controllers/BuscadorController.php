<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\Afiliado;
use App\Models\Conexion;
use App\Models\Factura;
use App\Models\Pago;
use App\Models\Lectura;
use App\Models\User;
use App\Models\Zona;

class BuscadorController extends Controller
{
    public function buscar(Request $request)
    {
        $query = trim($request->input('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $qLower = strtolower($query);
        $resultados = collect();

        /* =========================================
         * 1. AFILIADOS
         * ========================================= */
        try {
            if (Schema::hasTable('afiliados')) {
                $afiliados = Afiliado::query()
                    ->leftJoin('zonas', 'zonas.id', '=', 'afiliados.zona_id')
                    ->whereRaw("LOWER(afiliados.nombre_completo) LIKE ?", ["%{$qLower}%"])
                    ->orWhereRaw("LOWER(afiliados.ci) LIKE ?", ["%{$qLower}%"])
                    ->orWhereRaw("LOWER(afiliados.codigo) LIKE ?", ["%{$qLower}%"])
                    ->orWhereRaw("LOWER(afiliados.celular) LIKE ?", ["%{$qLower}%"])
                    ->orWhereRaw("LOWER(afiliados.direccion) LIKE ?", ["%{$qLower}%"])
                    ->orWhereRaw("LOWER(afiliados.tipo) LIKE ?", ["%{$qLower}%"])
                    ->orWhereRaw("LOWER(zonas.nombre) LIKE ?", ["%{$qLower}%"])
                    ->select('afiliados.*', 'zonas.nombre as zona_nombre')
                    ->limit(10)
                    ->get()
                    ->map(function ($a) {
                        return [
                            'title'    => "Afiliado: {$a->nombre_completo}",
                            'subtitle' => "CI: {$a->ci} | Código: {$a->codigo} | Zona: " . ($a->zona_nombre ?? 'Sin zona'),
                            'icon'     => '👤',
                            'url'      => url('/afiliados/' . $a->id),
                        ];
                    });

                $resultados = $resultados->merge($afiliados);
            }
        } catch (\Throwable $e) {
            Log::error("ERROR AFILIADOS: " . $e->getMessage());
        }

        /* =========================================
         * 2. CONEXIONES
         * ========================================= */
        try {
            if (Schema::hasTable('conexiones')) {
                $conexiones = Conexion::query()
                    ->leftJoin('afiliados', 'afiliados.id', '=', 'conexiones.afiliado_id')
                    ->leftJoin('zonas', 'zonas.id', '=', 'conexiones.zona_id')
                    ->whereRaw("LOWER(conexiones.codigo_medidor) LIKE ?", ["%{$qLower}%"])
                    ->orWhereRaw("LOWER(conexiones.direccion) LIKE ?", ["%{$qLower}%"])
                    ->orWhereRaw("LOWER(conexiones.tipo_conexion) LIKE ?", ["%{$qLower}%"])
                    ->orWhereRaw("LOWER(conexiones.estado) LIKE ?", ["%{$qLower}%"])
                    ->orWhereRaw("LOWER(afiliados.nombre_completo) LIKE ?", ["%{$qLower}%"])
                    ->orWhereRaw("LOWER(zonas.nombre) LIKE ?", ["%{$qLower}%"])
                    ->select(
                        'conexiones.*',
                        'afiliados.nombre_completo as afiliado_nombre',
                        'zonas.nombre as zona_nombre'
                    )
                    ->limit(10)
                    ->get()
                    ->map(function ($c) {
                        return [
                            'title'    => "Medidor: {$c->codigo_medidor}",
                            'subtitle' => "{$c->direccion} | " . ($c->afiliado_nombre ?? 'Sin afiliado') . " | Zona: " . ($c->zona_nombre ?? 'Sin zona'),
                            'icon'     => '📟',
                            'url'      => url('/conexiones/' . $c->id),
                        ];
                    });

                $resultados = $resultados->merge($conexiones);
            }
        } catch (\Throwable $e) {
            Log::error("ERROR CONEXIONES: " . $e->getMessage());
        }

        /* =========================================
         * 3. FACTURAS
         * ========================================= */
        try {
            if (Schema::hasTable('facturas')) {
                $facturas = Factura::query()
                    ->leftJoin('conexiones', 'conexiones.id', '=', 'facturas.conexion_id')
                    ->leftJoin('afiliados', 'afiliados.id', '=', 'conexiones.afiliado_id')
                    ->whereRaw("LOWER(facturas.periodo) LIKE ?", ["%{$qLower}%"])
                    ->orWhereRaw("LOWER(facturas.estado) LIKE ?", ["%{$qLower}%"])
                    ->orWhereRaw("facturas.monto_total LIKE ?", ["%{$query}%"])
                    ->orWhereRaw("facturas.consumo_m3 LIKE ?", ["%{$query}%"])
                    ->orWhereRaw("facturas.deuda_pendiente LIKE ?", ["%{$query}%"])
                    ->orWhereRaw("LOWER(afiliados.nombre_completo) LIKE ?", ["%{$qLower}%"])
                    ->select(
                        'facturas.*',
                        'afiliados.nombre_completo as afiliado_nombre'
                    )
                    ->limit(10)
                    ->get()
                    ->map(function ($f) {
                        return [
                            'title'    => "Factura: {$f->periodo}",
                            'subtitle' => "Bs. {$f->monto_total} | {$f->afiliado_nombre}",
                            'icon'     => $f->estado === 'pagado' ? '✅' : '🧾',
                            'url'      => url('/facturas/' . $f->id),
                        ];
                    });

                $resultados = $resultados->merge($facturas);
            }
        } catch (\Throwable $e) {
            Log::error("ERROR FACTURAS: " . $e->getMessage());
        }

        /* =========================================
         * 4. PAGOS
         * ========================================= */
        try {
            if (Schema::hasTable('pagos')) {
                $pagos = Pago::query()
                    ->leftJoin('facturas', 'facturas.id', '=', 'pagos.factura_id')
                    ->leftJoin('conexiones', 'conexiones.id', '=', 'facturas.conexion_id')
                    ->leftJoin('afiliados', 'afiliados.id', '=', 'conexiones.afiliado_id')
                    ->whereRaw("pagos.monto_pagado LIKE ?", ["%{$query}%"])
                    ->orWhereRaw("LOWER(pagos.metodo_pago) LIKE ?", ["%{$qLower}%"])
                    ->orWhereRaw("LOWER(afiliados.nombre_completo) LIKE ?", ["%{$qLower}%"])
                    ->orWhereRaw("pagos.fecha_pago LIKE ?", ["%{$query}%"])
                    ->select(
                        'pagos.*',
                        'afiliados.nombre_completo as afiliado_nombre'
                    )
                    ->limit(10)
                    ->get()
                    ->map(function ($p) {
                        return [
                            'title'    => "Pago Bs. {$p->monto_pagado}",
                            'subtitle' => "Fecha: {$p->fecha_pago} | {$p->afiliado_nombre}",
                            'icon'     => '💰',
                            'url'      => url('/pagos/' . $p->id),
                        ];
                    });

                $resultados = $resultados->merge($pagos);
            }
        } catch (\Throwable $e) {
            Log::error("ERROR PAGOS: " . $e->getMessage());
        }

        /* =========================================
         * 5. USUARIOS
         * ========================================= */
        try {
            if (Schema::hasTable('users')) {
                $usuarios = User::query()
                    ->leftJoin('afiliados', 'afiliados.id', '=', 'users.afiliado_id')
                    ->whereRaw("LOWER(users.name) LIKE ?", ["%{$qLower}%"])
                    ->orWhereRaw("LOWER(users.email) LIKE ?", ["%{$qLower}%"])
                    ->orWhereRaw("LOWER(afiliados.nombre_completo) LIKE ?", ["%{$qLower}%"])
                    ->select(
                        'users.*',
                        'afiliados.nombre_completo as afiliado_nombre'
                    )
                    ->limit(10)
                    ->get()
                    ->map(function ($u) {
                        return [
                            'title'    => "Usuario: {$u->name}",
                            'subtitle' => $u->afiliado_nombre ? "Afiliado: {$u->afiliado_nombre}" : $u->email,
                            'icon'     => '👨‍💻',
                            'url'      => url('/users/' . $u->id),
                        ];
                    });

                $resultados = $resultados->merge($usuarios);
            }
        } catch (\Throwable $e) {
            Log::error("ERROR USERS: " . $e->getMessage());
        }

        /* =========================================
         * RESPUESTA FINAL
         * ========================================= */
        return response()->json(
            $resultados->take(20)->values()
        );
    }
}
