<?php

namespace App\Http\Controllers;

// --- AÑADE ESTAS LÍNEAS 'USE' ---
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
// ---------------------------------

use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    // --- AÑADE ESTA LÍNEA 'USE' ---
    use AuthorizesRequests, ValidatesRequests;
    // ---------------------------------
}