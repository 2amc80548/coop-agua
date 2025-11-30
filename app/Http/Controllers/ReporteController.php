<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Pago;
use App\Models\Factura;
use App\Models\Afiliado;
use App\Models\Reclamo;
use App\Models\Lectura;
use App\Models\Conexion;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReporteController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Administrador|Secretaria');
    }

    public function index(Request $request)
    {
        // ========================
        // 1. VALIDAR FILTROS
        // ========================
        $validated = $request->validate([
            'fecha_inicio'     => ['nullable', 'date'],
            'fecha_fin'        => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'zona_id'          => ['nullable', 'integer'],
            'estado_servicio'  => ['nullable', 'string'],
        ]);

        // Rango de fechas por defecto: últimos 30 días
        $fechaInicio = !empty($validated['fecha_inicio'])
            ? Carbon::parse($validated['fecha_inicio'])->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();

        $fechaFin = !empty($validated['fecha_fin'])
            ? Carbon::parse($validated['fecha_fin'])->endOfDay()
            : Carbon::now()->endOfDay();

        $zonaId         = $validated['zona_id']         ?? null;
        $estadoServicio = $validated['estado_servicio'] ?? null;

        // =========================================
        // 2. QUERIES BASE CON FILTROS COMPARTIDOS
        // =========================================

        // ---- Afiliados base ----
        $afiliadosBase = Afiliado::query();

        if ($zonaId) {
            $afiliadosBase->where('zona_id', $zonaId);
        }

        if ($estadoServicio) {
            $afiliadosBase->where('estado_servicio', $estadoServicio);
        }

        $afiliadosTotalesQuery     = (clone $afiliadosBase);
        $afiliadosPorEstadoQuery   = (clone $afiliadosBase);
        $adultosMayoresQuery       = (clone $afiliadosBase);
        $nuevosAfiliadosQuery      = (clone $afiliadosBase);

        // ---- Conexiones base ----
        $conexionesBase = Conexion::query();

        if ($zonaId || $estadoServicio) {
            $conexionesBase->whereHas('afiliado', function ($q) use ($zonaId, $estadoServicio) {
                if ($zonaId) {
                    $q->where('zona_id', $zonaId);
                }
                if ($estadoServicio) {
                    $q->where('estado_servicio', $estadoServicio);
                }
            });
        }

        $conexionesTotalesQuery   = (clone $conexionesBase);
        $conexionesPorEstadoQuery = (clone $conexionesBase);
        $conexionesPorTipoQuery   = (clone $conexionesBase);
        $conexionesPorZonaQuery   = (clone $conexionesBase);

        // ---- Facturas base (por fecha de emisión) ----
        $facturasBase = Factura::query()
            ->whereBetween('fecha_emision', [$fechaInicio, $fechaFin]);

        if ($zonaId || $estadoServicio) {
            $facturasBase->whereHas('conexion.afiliado', function ($q) use ($zonaId, $estadoServicio) {
                if ($zonaId) {
                    $q->where('zona_id', $zonaId);
                }
                if ($estadoServicio) {
                    $q->where('estado_servicio', $estadoServicio);
                }
            });
        }

        $facturasTotalesQuery   = (clone $facturasBase);
        $facturasPorEstadoQuery = (clone $facturasBase);
        $morosidadQuery         = (clone $facturasBase);

        // ---- Pagos base (por fecha de pago) ----
        $pagosBase = Pago::query()
            ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin]);

        if ($zonaId || $estadoServicio) {
            $pagosBase->whereHas('factura.conexion.afiliado', function ($q) use ($zonaId, $estadoServicio) {
                if ($zonaId) {
                    $q->where('zona_id', $zonaId);
                }
                if ($estadoServicio) {
                    $q->where('estado_servicio', $estadoServicio);
                }
            });
        }

        $pagosTotalesQuery   = (clone $pagosBase);
        $pagosPorMetodoQuery = (clone $pagosBase);

        // ---- Reclamos base ----
        $reclamosBase = Reclamo::query()
            ->whereBetween('created_at', [$fechaInicio, $fechaFin]);

        if ($zonaId || $estadoServicio) {
            $reclamosBase->whereHas('afiliado', function ($qa) use ($zonaId, $estadoServicio) {
                if ($zonaId) {
                    $qa->where('zona_id', $zonaId);
                }
                if ($estadoServicio) {
                    $qa->where('estado_servicio', $estadoServicio);
                }
            });
        }

        $reclamosPorEstadoQuery   = (clone $reclamosBase);
        $reclamosResueltosQuery   = (clone $reclamosBase);

        // ---- Lecturas base (fecha_lectura) ----
        $lecturasBase = Lectura::query()
            ->whereBetween('fecha_lectura', [$fechaInicio, $fechaFin]);

        if ($zonaId || $estadoServicio) {
            $lecturasBase->whereHas('conexion.afiliado', function ($q) use ($zonaId, $estadoServicio) {
                if ($zonaId) {
                    $q->where('zona_id', $zonaId);
                }
                if ($estadoServicio) {
                    $q->where('estado_servicio', $estadoServicio);
                }
            });
        }

        $lecturasTotalesQuery = (clone $lecturasBase);
        $lecturasConsumoQuery = (clone $lecturasBase);

        // ---- Usuarios base (para nuevos usuarios) ----
        $usuariosBase = User::query();

        if ($zonaId || $estadoServicio) {
            $usuariosBase->whereHas('afiliado', function ($q) use ($zonaId, $estadoServicio) {
                if ($zonaId) {
                    $q->where('zona_id', $zonaId);
                }
                if ($estadoServicio) {
                    $q->where('estado_servicio', $estadoServicio);
                }
            });
        }

        $nuevosUsuariosQuery = (clone $usuariosBase);

        // =====================================
        // 3. CALCULAMOS LAS MÉTRICAS PRINCIPALES
        // =====================================

        // --- PAGOS ---
        $totalRecaudado = (float) $pagosTotalesQuery->sum('monto_pagado');
        $cantidadPagos  = (int)   $pagosBase->count();

        $pagosPorMetodo = $pagosPorMetodoQuery
            ->select('forma_pago', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(monto_pagado) as total_monto'))
            ->groupBy('forma_pago')
            ->orderByDesc('total_monto')
            ->get();

        // --- FACTURAS ---
        $totalFacturas = (int) $facturasTotalesQuery->count();

        $facturasPorEstado = $facturasPorEstadoQuery
            ->select('estado', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(monto_total) as total_monto'))
            ->groupBy('estado')
            ->get();

        $totalDeudaPendiente = (float) $morosidadQuery
            ->where('estado', 'impaga')
            ->sum('deuda_pendiente');

        // --- AFILIADOS ---
        $totalAfiliados = (int) $afiliadosTotalesQuery->count();

        $afiliadosPorEstado = $afiliadosPorEstadoQuery
            ->select('estado_servicio', DB::raw('COUNT(*) as cantidad'))
            ->groupBy('estado_servicio')
            ->get();

        $totalAdultosMayores = (int) $adultosMayoresQuery
            ->where('adulto_mayor', true)
            ->count();

        // NUEVOS AFILIADOS EN EL RANGO
        $nuevosAfiliados = (int) $nuevosAfiliadosQuery
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->count();

        // --- CONEXIONES ---
        $totalConexiones = (int) $conexionesTotalesQuery->count();

        $conexionesPorEstado = $conexionesPorEstadoQuery
            ->select('estado', DB::raw('COUNT(*) as cantidad'))
            ->groupBy('estado')
            ->get();

        $conexionesPorTipo = $conexionesPorTipoQuery
            ->select('tipo_conexion', DB::raw('COUNT(*) as cantidad'))
            ->groupBy('tipo_conexion')
            ->get();

        $conexionesPorZona = $conexionesPorZonaQuery
            ->join('zonas', 'zonas.id', '=', 'conexiones.zona_id')
            ->select('zonas.nombre as zona', DB::raw('COUNT(conexiones.id) as cantidad'))
            ->groupBy('zonas.nombre')
            ->orderByDesc('cantidad')
            ->get();

        // --- RECLAMOS ---
        $reclamosRecibidos = (int) $reclamosBase->count();

        $reclamosPorEstado = $reclamosPorEstadoQuery
            ->select('estado', DB::raw('COUNT(*) as cantidad'))
            ->groupBy('estado')
            ->get();

        $reclamosResueltos = (int) $reclamosResueltosQuery
            ->whereIn('estado', ['Resuelto', 'Cerrado'])
            ->count();

        // --- LECTURAS ---
        $lecturasRegistradas = (int) $lecturasTotalesQuery->count();

        $consumoPromedioM3 = (float) $lecturasConsumoQuery
            ->select(DB::raw('AVG(lectura_actual - lectura_anterior) as consumo_promedio'))
            ->value('consumo_promedio') ?? 0;

        // --- USUARIOS ---
        $totalUsuarios = (int) User::count();

        $nuevosUsuarios = (int) $nuevosUsuariosQuery
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->count();

        // ============================
        // 4. RESPUESTA A VUE
        // ============================

        return Inertia::render('Reportes/Index', [
            'resumen' => [
                'rango' => [
                    'inicio' => $fechaInicio->format('Y-m-d'),
                    'fin'    => $fechaFin->format('Y-m-d'),
                ],

                // PAGOS
                'totalRecaudado'      => $totalRecaudado,
                'cantidadPagos'       => $cantidadPagos,

                // FACTURAS
                'totalFacturas'       => $totalFacturas,
                'totalDeudaPendiente' => $totalDeudaPendiente,

                // AFILIADOS
                'totalAfiliados'      => $totalAfiliados,
                'nuevosAfiliados'     => $nuevosAfiliados,
                'totalAdultosMayores' => $totalAdultosMayores,

                // CONEXIONES
                'totalConexiones'     => $totalConexiones,
                'consumoPromedioM3'   => round($consumoPromedioM3, 2),

                // RECLAMOS
                'reclamosRecibidos'   => $reclamosRecibidos,
                'reclamosResueltos'   => $reclamosResueltos,

                // LECTURAS
                'lecturasRegistradas' => $lecturasRegistradas,

                // USUARIOS
                'totalUsuarios'       => $totalUsuarios,
                'nuevosUsuarios'      => $nuevosUsuarios,
            ],

            // DETALLES PARA TABLAS / GRÁFICOS
            'detallePagos' => [
                'porMetodo' => $pagosPorMetodo,
            ],
            'detalleFacturas' => [
                'porEstado' => $facturasPorEstado,
            ],
            'detalleAfiliados' => [
                'porEstadoServicio' => $afiliadosPorEstado,
            ],
            'detalleReclamos' => [
                'porEstado' => $reclamosPorEstado,
            ],
            'detalleConexiones' => [
                'porEstado' => $conexionesPorEstado,
                'porTipo'   => $conexionesPorTipo,
                'porZona'   => $conexionesPorZona,
            ],

            // Filtros devueltos
            'filters' => [
                'fecha_inicio'    => $fechaInicio->format('Y-m-d'),
                'fecha_fin'       => $fechaFin->format('Y-m-d'),
                'zona_id'         => $zonaId,
                'estado_servicio' => $estadoServicio,
            ],
        ]);
    }

    /*
    public function exportPdf(Request $request)
    {
        // Podrías reutilizar la lógica de index() (mismo rango de fechas, filtros)
        // y luego hacer algo como:
        // $data = [...]; // mismo array que envías a Inertia
        // $pdf = \PDF::loadView('reportes.general-pdf', $data);
        // return $pdf->download('reporte_general.pdf');
    }
    */
}
