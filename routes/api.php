<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PagoController;
use App\Models\Afiliado;
use App\Http\Controllers\AfiliadoController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
/*
|--------------------------------------------------------------------------
| Endpoint para Callback de PagoFácil
|--------------------------------------------------------------------------
| 
/*
|--------------------------------------------------------------------------
| Endpoint de Salud del Sistema (Health Check)
|--------------------------------------------------------------------------
| Utilizado por Docker, scripts de despliegue y monitoreo para verificar
| que Laravel y la base de datos PostgreSQL estén respondiendo correctamente.
*/
Route::get('/health', function () {
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        return response()->json([
            'status' => 'ok',
            'database' => 'connected',
            'timestamp' => now()->toIso8601String(),
            'environment' => config('app.env'),
            'app_name' => config('app.name'),
        ], 200);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'database' => 'disconnected',
            'message' => $e->getMessage(),
            'timestamp' => now()->toIso8601String(),
        ], 500);
    }
});
