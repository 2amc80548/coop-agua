<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PagoController;
use App\Models\Afiliado;
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
/*
|--------------------------------------------------------------------------
| Endpoint para Callback de PagoFácil
|--------------------------------------------------------------------------
| URL Final: https://www.tecnoweb.org.bo/inf513/grupo06cc/metaluvcamiri/api/payment/callback
*/
Route::post('/payment/callback', [PagoController::class, 'callbackPagoFacil']);



Route::get('/afiliados/buscar', function (Request $request) {
    $query = $request->input('q');

    if (strlen($query) < 2) {
        return response()->json([]);
    }

    $afiliados = Afiliado::where('ci', 'like', "%{$query}%")
        ->orWhere('nombre_completo', 'like', "%{$query}%")
        ->withCount('users') 
        ->limit(10)
        ->get()
        ->map(function ($b) {
            return [
                'id' => $b->id,
                'nombre_completo' => $b->nombre_completo,
                'ci' => $b->ci,
                'usuarios_count' => $b->users_count, // ✅ 
                'puede_tener_mas' => $b->users_count < 2
            ];
        });

    return response()->json($afiliados);
});