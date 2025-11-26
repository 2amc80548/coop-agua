<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\Conexion;
use App\Models\Factura;
use App\Models\Pago;
use App\Models\Lectura;
use App\Models\Reclamo;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{


    public function index()
    {
        // ==============================
        // 1. RANGO: MES ACTUAL
        // ==============================
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes    = Carbon::now()->endOfDay();

        // Para gráficos últimos 6 meses
        $inicio6Meses = Carbon::now()->subMonths(5)->startOfMonth();

        // ==============================
        // 2. KPIs GENERALES
        // ==============================
        $totalAfiliados      = Afiliado::count();
        $totalConexiones     = Conexion::count();
        $totalUsuarios       = User::count();

        $totalFacturasMes    = Factura::whereBetween('fecha_emision', [$inicioMes, $finMes])->count();
        $totalPagosMes       = Pago::whereBetween('fecha_pago', [$inicioMes, $finMes])->count();
        $totalLecturasMes    = Lectura::whereBetween('fecha_lectura', [$inicioMes, $finMes])->count();
        $totalReclamosMes    = Reclamo::whereBetween('created_at', [$inicioMes, $finMes])->count();

        $montoFacturadoMes   = (float) Factura::whereBetween('fecha_emision', [$inicioMes, $finMes])->sum('monto_total');
        $montoDeudaMes       = (float) Factura::whereBetween('fecha_emision', [$inicioMes, $finMes])
                                    ->where('estado', 'impaga')
                                    ->sum('deuda_pendiente');
        $montoRecaudadoMes   = (float) Pago::whereBetween('fecha_pago', [$inicioMes, $finMes])->sum('monto_pagado');

        $nuevosAfiliadosMes  = Afiliado::whereBetween('created_at', [$inicioMes, $finMes])->count();
        $nuevosUsuariosMes   = User::whereBetween('created_at', [$inicioMes, $finMes])->count();

        $afiliadosActivos    = Afiliado::where('estado_servicio', 'activo')->count();
        $afiliadosEnMora     = Afiliado::where('estado_servicio', 'en_corte')->count();
        $afiliadosCortados   = Afiliado::where('estado_servicio', 'cortado')->count();
        $afiliadosPendientes = Afiliado::where('estado_servicio', 'pendiente')->count();

        $kpis = [
            'inicioMes'         => $inicioMes->format('Y-m-d'),
            'finMes'            => $finMes->format('Y-m-d'),
            'totalAfiliados'    => $totalAfiliados,
            'totalConexiones'   => $totalConexiones,
            'totalUsuarios'     => $totalUsuarios,
            'totalFacturasMes'  => $totalFacturasMes,
            'totalPagosMes'     => $totalPagosMes,
            'totalLecturasMes'  => $totalLecturasMes,
            'totalReclamosMes'  => $totalReclamosMes,
            'montoFacturadoMes' => $montoFacturadoMes,
            'montoDeudaMes'     => $montoDeudaMes,
            'montoRecaudadoMes' => $montoRecaudadoMes,
            'nuevosAfiliadosMes'=> $nuevosAfiliadosMes,
            'nuevosUsuariosMes' => $nuevosUsuariosMes,
            'afiliadosActivos'  => $afiliadosActivos,
            'afiliadosEnMora'   => $afiliadosEnMora,
            'afiliadosCortados' => $afiliadosCortados,
        ];

        // ==============================
        // 3. GRÁFICOS POR MES (6 MESES)
        // ==============================

        // Facturación por mes (últimos 6 meses)
        $facturacionPorMes = Factura::where('fecha_emision', '>=', $inicio6Meses)
            ->select(
                DB::raw("to_char(fecha_emision, 'YYYY-MM') as mes"),
                DB::raw('SUM(monto_total) as total_facturado')
            )
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        // Recaudación por mes
        $recaudacionPorMes = Pago::where('fecha_pago', '>=', $inicio6Meses)
            ->select(
                DB::raw("to_char(fecha_pago, 'YYYY-MM') as mes"),
                DB::raw('SUM(monto_pagado) as total_recaudado')
            )
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        // Nuevos afiliados por mes
        $nuevosAfiliadosPorMes = Afiliado::where('created_at', '>=', $inicio6Meses)
            ->select(
                DB::raw("to_char(created_at, 'YYYY-MM') as mes"),
                DB::raw('COUNT(*) as cantidad')
            )
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        // Facturas por estado (del mes actual)
        $facturasPorEstadoMes = Factura::whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->select('estado', DB::raw('COUNT(*) as cantidad'))
            ->groupBy('estado')
            ->get();

        // Afiliados por estado_servicio (global)
        $afiliadosPorEstadoServicio = Afiliado::select(
                'estado_servicio',
                DB::raw('COUNT(*) as cantidad')
            )
            ->groupBy('estado_servicio')
            ->get();

        // Conexiones por zona (top 5)
        $conexionesPorZona = Conexion::join('zonas', 'zonas.id', '=', 'conexiones.zona_id')
            ->select('zonas.nombre as zona', DB::raw('COUNT(conexiones.id) as cantidad'))
            ->groupBy('zonas.nombre')
            ->orderByDesc('cantidad')
            ->limit(5)
            ->get();

        $charts = [
            'facturacionPorMes'        => $facturacionPorMes,
            'recaudacionPorMes'        => $recaudacionPorMes,
            'nuevosAfiliadosPorMes'    => $nuevosAfiliadosPorMes,
            'facturasPorEstadoMes'     => $facturasPorEstadoMes,
            'afiliadosPorEstadoServicio'=> $afiliadosPorEstadoServicio,
            'conexionesPorZona'        => $conexionesPorZona,
        ];

        // ==============================
        // 4. TOPS
        // ==============================

        // TOP 10 DEUDORES (en el mes)
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


        // TOP 10 MÁS PUNTUALES EN PAGAR (en el mes)
$topPuntuales = Afiliado::select(
        'afiliados.id',
        'afiliados.nombre_completo',
        DB::raw('COUNT(facturas.id) as total_facturas'),
        DB::raw("SUM(
            CASE 
                WHEN facturas.estado = 'pagado' 
                     AND facturas.fecha_pago IS NOT NULL 
                     AND facturas.fecha_vencimiento IS NOT NULL
                     AND facturas.fecha_pago <= facturas.fecha_vencimiento
                THEN 1 ELSE 0 END
        ) as pagos_puntuales"),
        DB::raw("ROUND(
            CASE 
                WHEN COUNT(facturas.id) = 0 THEN 0
                ELSE (SUM(
                    CASE 
                        WHEN facturas.estado = 'pagado' 
                             AND facturas.fecha_pago IS NOT NULL 
                             AND facturas.fecha_vencimiento IS NOT NULL
                             AND facturas.fecha_pago <= facturas.fecha_vencimiento
                        THEN 1 ELSE 0 END
                )::decimal / COUNT(facturas.id)) * 100
            END
        ,2) as porcentaje_puntualidad")
    )
    ->join('conexiones', 'conexiones.afiliado_id', '=', 'afiliados.id')
    ->join('facturas', 'facturas.conexion_id', '=', 'conexiones.id')
    ->whereBetween('facturas.fecha_emision', [$inicioMes, $finMes])
    ->groupBy('afiliados.id', 'afiliados.nombre_completo')
    ->havingRaw('COUNT(facturas.id) >= 3')
    ->orderByDesc('porcentaje_puntualidad')
    ->orderByDesc('pagos_puntuales')
    ->limit(10)
    ->get();

    $tops = [
    'deudores'  => $topDeudores,
    'puntuales' => $topPuntuales,
];

        // ==============================
        // 5. ACTIVIDAD RECIENTE
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
        // 6. ENVIAR A INERTIA
        // ==============================
        return Inertia::render('Dashboard/Admin', [
            'kpis'   => $kpis,
            'charts' => $charts,
            'tops'   => $tops,
            'recent' => $recent,
        ]);
    }
}
