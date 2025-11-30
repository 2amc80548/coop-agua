<?php

namespace App\Http\Controllers;

use App\Models\Lectura;
use App\Models\Conexion;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Inertia\Inertia;
use DB;

class TecnicoDashboardController extends Controller
{
    public function index()
    {
        $periodo = Carbon::now()->format('Y-m');
        $user = Auth::id();

        /*
        |--------------------------------------------------------------------------
        | KPIs DEL TÉCNICO
        |--------------------------------------------------------------------------
        */
        $lecturasMes = Lectura::where('registrado_por', $user)
            ->where('periodo', $periodo)
            ->count();

        $totalConexiones = Conexion::where('estado', 'activo')->count();

        $conexionesLecturadasMes = Lectura::where('periodo', $periodo)
            ->pluck('conexion_id')
            ->unique()
            ->count();

        $pendientesMes = $totalConexiones - $conexionesLecturadasMes;

        $avanceMes = $totalConexiones > 0 
            ? round(($conexionesLecturadasMes / $totalConexiones) * 100, 2)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | AVANCE POR ZONA / BARRIO
        |--------------------------------------------------------------------------
        */
 $avanceZonas = DB::table('conexiones')
            ->join('zonas', 'zonas.id', '=', 'conexiones.zona_id')
            ->leftJoin('lecturas', function ($join) use ($periodo) {
                $join->on('lecturas.conexion_id', '=', 'conexiones.id')
                     ->where('lecturas.periodo', '=', $periodo);
            })
            ->where('conexiones.estado', 'activo') // solo activas, igual que el KPI
            ->groupBy('zonas.nombre')
            ->selectRaw('
                zonas.nombre AS zona,
                COUNT(DISTINCT conexiones.id) AS total_conexiones,
                COUNT(DISTINCT lecturas.id) AS lecturadas
            ')
            ->orderBy('zonas.nombre')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | GRAFICO: LECTURAS ÚLTIMOS 6 MESES
        |--------------------------------------------------------------------------
        */
        $rendimiento = Lectura::selectRaw("periodo, COUNT(*) as total")
            ->where('registrado_por', $user)
            ->groupBy('periodo')
            ->orderBy('periodo', 'desc')
            ->limit(6)
            ->get();

        return Inertia::render('Dashboard/Tecnico', [
            'kpis' => [
                'lecturasMes' => $lecturasMes,
                'pendientesMes' => $pendientesMes,
                'totalConexiones' => $totalConexiones,
                'avanceMes' => $avanceMes,
            ],
            'charts' => [
                'rendimientoMes' => $rendimiento,
            ],
            'avanceZonas' => $avanceZonas,
            'periodoActual' => $periodo,
        ]);
    }
}
