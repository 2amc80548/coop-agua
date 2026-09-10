<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ActivityLogController extends Controller
{
    /**
     * Muestra la bitácora de auditoría del sistema con búsqueda, filtros y paginación.
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('user:id,name,email');

        // Búsqueda por texto (descripción, acción, módulo, IP o nombre/email de usuario)
        $query->when($request->input('search'), function ($q, $search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('descripcion', 'like', "%{$search}%")
                    ->orWhere('accion', 'like', "%{$search}%")
                    ->orWhere('modulo', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uQuery) use ($search) {
                        $uQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        });

        // Filtro por Módulo
        $query->when($request->input('modulo'), function ($q, $modulo) {
            $q->where('modulo', $modulo);
        });

        // Filtro por Usuario
        $query->when($request->input('user_id'), function ($q, $userId) {
            $q->where('user_id', $userId);
        });

        // Filtro por Rango de Fechas
        $query->when($request->filled('fecha_inicio') && $request->filled('fecha_fin'), function ($q) use ($request) {
            try {
                $inicio = Carbon::parse($request->fecha_inicio)->startOfDay();
                $fin = Carbon::parse($request->fecha_fin)->endOfDay();
                $q->whereBetween('created_at', [$inicio, $fin]);
            } catch (\Exception $e) { /* Ignorar fechas inválidas */ }
        });

        // Lista de módulos únicos registrados para los filtros
        $modulos = ActivityLog::select('modulo')
            ->whereNotNull('modulo')
            ->where('modulo', '!=', '')
            ->distinct()
            ->orderBy('modulo')
            ->pluck('modulo');

        // Lista de usuarios registrados para los filtros
        $usuarios = User::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Bitacora/Index', [
            'logs' => $query->orderBy('created_at', 'desc')
                            ->paginate(15)
                            ->withQueryString(),
            'filters' => [
                'search' => $request->input('search'),
                'modulo' => $request->input('modulo'),
                'user_id' => $request->input('user_id'),
                'fecha_inicio' => $request->input('fecha_inicio'),
                'fecha_fin' => $request->input('fecha_fin'),
            ],
            'modulos' => $modulos,
            'usuarios' => $usuarios,
        ]);
    }
}
