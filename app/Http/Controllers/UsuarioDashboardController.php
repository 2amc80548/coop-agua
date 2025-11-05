<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Para saber qué usuario está logueado
use Inertia\Inertia;
use App\Models\Afiliado; // Para buscar al afiliado
use App\Models\Factura;  // Para calcular deudas
use App\Models\Lectura;  // Para ver el último consumo
use Illuminate\Support\Facades\Log; // Para registrar errores

class UsuarioDashboardController extends Controller
{
    /**
     * Muestra el dashboard principal del Afiliado (Rol "Usuario").
     * Recopila un resumen de toda su información clave.
     */
    public function index()
    {
        try {
            // 1. Obtener el usuario autenticado y su ID de afiliado
            $user = Auth::user();
            
            // 2. Verificar que el usuario esté vinculado a un afiliado
            if (!$user->afiliado_id) {
                Log::warning("Usuario {$user->id} ({$user->email}) intentó acceder al dashboard sin afiliado_id.");
                return Inertia::render('Dashboard/Usuario', [
                    'summary' => null, // Enviar null para que la vista muestre el error
                    'error' => 'Tu cuenta de usuario no está vinculada a un perfil de Afiliado. Por favor, contacta con la cooperativa.'
                ]);
            }

            // 3. Obtener el Afiliado y sus conexiones (solo los IDs)
            $afiliado = Afiliado::find($user->afiliado_id);
            
            if (!$afiliado) {
                 Log::error("Usuario {$user->id} vinculado a afiliado_id {$user->afiliado_id} no encontrado.");
                 return Inertia::render('Dashboard/Usuario', [
                    'summary' => null,
                    'error' => 'El perfil de Afiliado vinculado a tu cuenta no fue encontrado (ID: '.$user->afiliado_id.').'
                ]);
            }
            
            // Obtenemos los IDs de todas las conexiones (medidores) de este afiliado
            $conexionIds = $afiliado->conexiones()->pluck('id');

            // 4. Calcular Resumen de Deudas (usando las columnas que creamos)
            //    Buscamos facturas donde la conexión esté en $conexionIds Y el estado sea 'impaga'
            $queryDeuda = Factura::whereIn('conexion_id', $conexionIds)
                                 ->where('estado', 'impaga'); // ¡Tu estado!

            // Usamos 'deuda_pendiente' para un cálculo súper rápido
            $deudaTotal = $queryDeuda->sum('deuda_pendiente'); 
            $facturasPendientesCount = $queryDeuda->count();

            // 5. Obtener Último Consumo
            $ultimoConsumo = 0;
            $ultimoPeriodo = 'N/A';
            
            $ultimaLectura = Lectura::whereIn('conexion_id', $conexionIds)
                                    ->orderBy('periodo', 'desc') // Ordenar por el período más reciente
                                    ->first();

            if ($ultimaLectura) {
                $ultimoConsumo = $ultimaLectura->lectura_actual - $ultimaLectura->lectura_anterior;
                $ultimoPeriodo = $ultimaLectura->periodo;
            }

            // 6. Enviar todos los datos a la vista Vue
            return Inertia::render('Dashboard/Usuario', [
                'summary' => [
                    'afiliado_nombre' => $afiliado->nombre_completo,
                    'afiliado_codigo' => $afiliado->codigo,
                    'estado_servicio' => $afiliado->estado_servicio, // 'activo', 'en_corte', 'cortado'
                    'deuda_total' => (float) $deudaTotal,
                    'facturas_pendientes_count' => $facturasPendientesCount,
                    'ultimo_consumo_m3' => (float) $ultimoConsumo,
                    'ultimo_periodo' => $ultimoPeriodo,
                ],
                'error' => null // No hay error
            ]);

        } catch (\Exception $e) {
            Log::critical('Error mayor en UsuarioDashboardController:', ['error' => $e->getMessage()]);
            return Inertia::render('Dashboard/Usuario', [
                'summary' => null,
                'error' => 'Ocurrió un error inesperado al cargar tu información. Por favor, reporta este problema.'
            ]);
        }
    }
}