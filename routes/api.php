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
| 
*/
Route::post('/payment/callback', [PagoController::class, 'callbackPagoFacil']);



Route::get('/afiliados/buscar', function (Request $request) {
  
    $query = $request->input('q') ?? $request->input('term'); 

    if (strlen($query) < 2) {
        return response()->json([]);
    }

    $afiliados = Afiliado::where('ci', 'like', "%{$query}%")
        ->orWhere('nombre_completo', 'like', "%{$query}%")
        ->orWhere('codigo', 'like', "%{$query}%") 
        ->limit(10)
        ->get()
        ->map(function ($b) {
            return [
                'id' => $b->id,
                'nombre_completo' => $b->nombre_completo,
                'ci' => $b->ci,
                'codigo' => $b->codigo,
                'direccion' => $b->direccion,
                'zona_id' => $b->zona_id,     
            ];
        });

    return response()->json($afiliados);
});