<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\Conexion;
use App\Models\Factura;
use App\Models\Pago;
use App\Models\Lectura;
use App\Models\Reclamo;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class SecretariaDashboardController extends Controller
{
    public function index()
    {
        // ==============================
        // 1. RANGO MES ACTUAL
        // ==============================
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes    = Carbon::now()->endOfDay();
        $inicio6Meses = Carbon::now()->subMonths(5)->startOfMonth();

        // ==============================
        // 2. KPIs PRINCIPALES
        // ==============================
        $totalAfiliados    = Afiliado::count();
        $totalConexiones   = Conexion::count();

        $totalFacturasMes  = Factura::whereBetween('fecha_emision', [$inicioMes, $finMes])->count();
        $totalPagosMes     = Pago::whereBetween('fecha_pago', [$inicioMes, $finMes])->count();
        $totalReclamosMes  = Reclamo::whereBetween('created_at', [$inicioMes, $finMes])->count();

        $montoFacturadoMes = (float) Factura::whereBetween('fecha_emision', [$inicioMes, $finMes])
                                ->sum('monto_total');
        $montoDeudaMes     = (float) Factura::whereBetween('fecha_emision', [$inicioMes, $finMes])
                                ->where('estado', 'impaga')
                                ->sum('deuda_pendiente');
        $montoRecaudadoMes = (float) Pago::whereBetween('fecha_pago', [$inicioMes, $finMes])
                                ->sum('monto_pagado');

        $nuevosAfiliadosMes = Afiliado::whereBetween('created_at', [$inicioMes, $finMes])->count();

        $afiliadosActivos    = Afiliado::where('estado_servicio', 'activo')->count();
        $afiliadosEnMora     = Afiliado::where('estado_servicio', 'en_corte')->count();
        $afiliadosCortados   = Afiliado::where('estado_servicio', 'cortado')->count();
        $afiliadosPendientes = Afiliado::where('estado_servicio', 'pendiente')->count();

        $kpis = [
            'inicioMes'          => $inicioMes->format('Y-m-d'),
            'finMes'             => $finMes->format('Y-m-d'),
            'totalAfiliados'     => $totalAfiliados,
            'totalConexiones'    => $totalConexiones,
            'totalFacturasMes'   => $totalFacturasMes,
            'totalPagosMes'      => $totalPagosMes,
            'totalReclamosMes'   => $totalReclamosMes,
            'montoFacturadoMes'  => $montoFacturadoMes,
            'montoDeudaMes'      => $montoDeudaMes,
            'montoRecaudadoMes'  => $montoRecaudadoMes,
            'nuevosAfiliadosMes' => $nuevosAfiliadosMes,
            'afiliadosActivos'   => $afiliadosActivos,
            'afiliadosEnMora'    => $afiliadosEnMora,
            'afiliadosCortados'  => $afiliadosCortados,
            'afiliadosPendientes'=> $afiliadosPendientes,
        ];

        // ==============================
        // 3. GRÁFICOS
        // ==============================
        $facturacionPorMes = Factura::where('fecha_emision', '>=', $inicio6Meses)
            ->select(
                DB::raw("to_char(fecha_emision, 'YYYY-MM') as mes"),
                DB::raw('SUM(monto_total) as total_facturado')
            )
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $recaudacionPorMes = Pago::where('fecha_pago', '>=', $inicio6Meses)
            ->select(
                DB::raw("to_char(fecha_pago, 'YYYY-MM') as mes"),
                DB::raw('SUM(monto_pagado) as total_recaudado')
            )
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $nuevosAfiliadosPorMes = Afiliado::where('created_at', '>=', $inicio6Meses)
            ->select(
                DB::raw("to_char(created_at, 'YYYY-MM') as mes"),
                DB::raw('COUNT(*) as cantidad')
            )
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $facturasPorEstadoMes = Factura::whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->select('estado', DB::raw('COUNT(*) as cantidad'))
            ->groupBy('estado')
            ->get();

        $afiliadosPorEstadoServicio = Afiliado::select(
                'estado_servicio',
                DB::raw('COUNT(*) as cantidad')
            )
            ->groupBy('estado_servicio')
            ->get();

        $conexionesPorZona = Conexion::join('zonas', 'zonas.id', '=', 'conexiones.zona_id')
            ->select('zonas.nombre as zona', DB::raw('COUNT(conexiones.id) as cantidad'))
            ->groupBy('zonas.nombre')
            ->orderByDesc('cantidad')
            ->limit(5)
            ->get();

        $charts = [
            'facturacionPorMes'         => $facturacionPorMes,
            'recaudacionPorMes'         => $recaudacionPorMes,
            'nuevosAfiliadosPorMes'     => $nuevosAfiliadosPorMes,
            'facturasPorEstadoMes'      => $facturasPorEstadoMes,
            'afiliadosPorEstadoServicio'=> $afiliadosPorEstadoServicio,
            'conexionesPorZona'         => $conexionesPorZona,
        ];

        // ==============================
        // 4. TOPS
        // ==============================
        $topDeudores = Afiliado::select(
                'afiliados.id',
                'afiliados.nombre_completo',
                DB::raw('SUM(facturas.deuda_pendiente) as deuda_total')
            )
            ->join('conexiones', 'conexiones.afiliado_id', '=', 'afiliados.id')
            ->join('facturas', 'facturas.conexion_id', '=', 'conexiones.id')
            ->whereBetween('facturas.fecha_emision', [$inicioMes, $finMes])
            ->where('facturas.estado', 'impaga')
            ->groupBy('afiliados.id', 'afiliados.nombre_completo')
            ->havingRaw('SUM(facturas.deuda_pendiente) > 0')
            ->orderByDesc('deuda_total')
            ->limit(10)
            ->get();

        $tops = [
            'deudores' => $topDeudores,
        ];

        // ==============================
        // 5. ACTUALIDAD
        // ==============================
        $ultimosPagos = Pago::with(['factura.conexion.afiliado'])
            ->orderByDesc('fecha_pago')
            ->limit(5)
            ->get();

        $ultimosReclamos = Reclamo::with(['afiliado'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recent = [
            'pagos'    => $ultimosPagos,
            'reclamos' => $ultimosReclamos,
        ];

        // ==============================
        // 6. ENVIAR A VISTA
        // ==============================
        return Inertia::render('Dashboard/Secretaria', [
            'kpis'   => $kpis,
            'charts' => $charts,
            'tops'   => $tops,
            'recent' => $recent,
        ]);
    }
}
