<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


use App\Models\Beneficiario;

Route::get('/beneficiarios/buscar/{ci}', function ($ci) {
    $beneficiario = Beneficiario::where('ci', $ci)->first();

    if (!$beneficiario) {
        return response()->json(['message' => 'No encontrado'], 404);
    }

    return response()->json([
        'id' => $beneficiario->id,
        'nombre_completo' => $beneficiario->nombre_completo,
        'tiene_usuario' => $beneficiario->user ? true : false
    ]);
});

use App\Http\Controllers\UserController;

Route::apiResource('users', UserController::class);
