<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Pago;
use App\Models\Factura;
use App\Models\Afiliado;
use App\Models\Reclamo;
use App\Models\Lectura;
use Carbon\Carbon; // ¡Importante para fechas!

class ReporteController extends Controller
{
    /**
     * Define los permisos para este controlador.
     * Solo Admin y Secretaria pueden ver reportes.
     */
    // public function __construct()
    // {
    //     $this->middleware('role:Administrador|Secretaria');
    // }

    /**
     * Muestra el Dashboard de Reportes y Estadísticas.
     * Calcula todos los KPIs basados en un rango de fechas.
     */
    public function index(Request $request)
    {
        // 1. Validar los filtros de fecha
        $validated = $request->validate([
            // Por defecto, muestra el mes actual
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        // 2. Definir el rango de fechas (si no hay, usa el mes actual)
        $fechaInicio = Carbon::parse($request->input('fecha_inicio', Carbon::now()->startOfMonth()));
        $fechaFin = Carbon::parse($request->input('fecha_fin', Carbon::now()->endOfDay()));

        // --- 3. CÁLCULO DE KPIs (Consultas rápidas y eficientes) ---

        // A. KPI de Pagos (Total Recaudado en el rango)
        $totalRecaudado = Pago::whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
                              ->sum('monto_pagado');
                              
        $conteoPagos = Pago::whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
                             ->count();

        // B. KPI de Facturación (Total Facturado en el rango)
        $totalFacturado = Factura::whereBetween('fecha_emision', [$fechaInicio, $fechaFin])
                                 ->where('estado', '!=', 'anulada') // No contar anuladas
                                 ->sum('monto_total');
        $conteoFacturas = Factura::whereBetween('fecha_emision', [$fechaInicio, $fechaFin])
                                   ->where('estado', '!=', 'anulada')
                                   ->count();
                                   
        // C. KPI de Deudas (Valores GLOBALES, no por fecha)
        $deudaTotalGlobal = Factura::where('estado', 'impaga')->sum('deuda_pendiente');
        $conteoDeudores = Factura::where('estado', 'impaga')
                                 ->distinct('conexion_id') // Contar conexiones únicas con deuda
                                 ->count('conexion_id');
        
        // D. KPI de Crecimiento (Nuevos Afiliados en el rango)
        $nuevosAfiliados = Afiliado::whereBetween('fecha_afiliacion', [$fechaInicio, $fechaFin])
                                   ->count();
                                   
        // E. KPI de Reclamos (Reclamos recibidos en el rango)
        $reclamosRecibidos = Reclamo::whereBetween('created_at', [$fechaInicio, $fechaFin])
                                    ->count();
        $reclamosResueltos = Reclamo::whereBetween('updated_at', [$fechaInicio, $fechaFin])
                                    ->whereIn('estado', ['Resuelto', 'Cerrado'])
                                    ->count();
        
        // F. KPI de Operaciones (Lecturas registradas en el rango)
        $lecturasRegistradas = Lectura::whereBetween('fecha_lectura', [$fechaInicio, $fechaFin])
                                      ->count();


        // 4. Enviar todos los datos a UNA SOLA VISTA
        return Inertia::render('Reportes/Index', [
            'stats' => [ // Un solo objeto con todos los resúmenes
                'recaudado' => (float) $totalRecaudado,
                'conteoPagos' => $conteoPagos,
                'facturado' => (float) $totalFacturado,
                'conteoFacturas' => $conteoFacturas,
                'deudaGlobal' => (float) $deudaTotalGlobal,
                'conteoDeudores' => $conteoDeudores,
                'nuevosAfiliados' => $nuevosAfiliados,
                'reclamosRecibidos' => $reclamosRecibidos,
                'reclamosResueltos' => $reclamosResueltos,
                'lecturasRegistradas' => $lecturasRegistradas,
            ],
            'filters' => [ // Enviar los filtros de vuelta
                'fecha_inicio' => $fechaInicio->format('Y-m-d'),
                'fecha_fin' => $fechaFin->format('Y-m-d'),
            ],
            // (Opcional) Enviar datos para PDFs (esto lo vemos luego)
            // 'exportUrl' => route('reportes.exportar', $validated)
        ]);
    }

    // (Aquí irán las funciones de EXPORTAR, ej. exportarPagosPDF)
}