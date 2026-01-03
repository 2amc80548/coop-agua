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
*/
Route::post('/payment/callback', [PagoController::class, 'callbackPagoFacil']);





    //  Route::get('afiliados/buscar', [AfiliadoController::class, 'apiSearch']);


