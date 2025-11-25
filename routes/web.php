<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// --- Controladores de Dashboard ---
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\SecretariaDashboardController;
use App\Http\Controllers\TecnicoDashboardController;
use App\Http\Controllers\UsuarioDashboardController;

// --- Otros Controladores ---
use App\Http\Controllers\UserController;
use App\Http\Controllers\AfiliadoController;
use App\Http\Controllers\ConexionController;
use App\Http\Controllers\LecturaController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\TarifaController;
use App\Http\Controllers\TarifaConceptoController;
use App\Http\Controllers\FacturacionController;
use App\Http\Controllers\ZonaController;
use App\Http\Controllers\ReclamoController;
use App\Http\Controllers\ReclamoTipoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\BuscadorController;

// ================================
// Página de bienvenida
// ================================
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'     => Route::has('login'),
        'canRegister'  => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'   => PHP_VERSION,
    ]);
});

// ================================
// Redirección de Dashboard 
// ================================
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->get('/dashboard', function () {
    $user = auth()->user();

    if ($user->hasRole('Administrador')) {
        return redirect()->route('admin.dashboard');
    }
    if ($user->hasRole('Secretaria')) {
        return redirect()->route('secretaria.dashboard');
    }
    if ($user->hasRole('Tecnico')) {
        return redirect()->route('tecnico.dashboard');
    }
    if ($user->hasRole('Usuario')) {
        
        // 1. Si es "Usuario" PERO NO ESTÁ VINCULADO...
        if ($user->afiliado_id === null) {
            // ...lo mandamos a la sala de espera.
            return redirect()->route('usuario.pendiente'); 
        }
        
        // 2. Si es "Usuario" Y SÍ ESTÁ VINCULADO...
        // ...lo mandamos a su dashboard normal.
        return redirect()->route('usuario.dashboard');
    }
    
    // Si no tiene ningún rol (raro, pero posible), lo sacamos.
    Auth::logout();
    return redirect('/');
})->name('dashboard');


// ===============================================
// RUTAS PARA USUARIOS AUTENTICADOS (TODOS LOS ROLES)
// ===============================================
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {

    // --- DASHBOARDS (Por Rol) ---
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
         ->middleware('role:Administrador')
         ->name('admin.dashboard');

    Route::get('/secretaria/dashboard', [SecretariaDashboardController::class, 'index'])
         ->middleware('role:Secretaria')
         ->name('secretaria.dashboard');

    Route::get('/tecnico/dashboard', [TecnicoDashboardController::class, 'index'])
         ->middleware('role:Tecnico')
         ->name('tecnico.dashboard');

    Route::get('/usuario/dashboard', [UsuarioDashboardController::class, 'index'])
         ->middleware('role:Usuario')
         ->name('usuario.dashboard');


    // ===============================================
    // RUTAS DE GESTIÓN (ESTRUCTURA LIMPIA Y ÚNICA)
    // ===============================================

    // --- MÓDULOS CRUD ---
    Route::resource('users', UserController::class)
         ->middleware('role:Administrador');

    Route::resource('afiliados', AfiliadoController::class)
         ->middleware('role:Administrador|Secretaria');

    Route::resource('conexiones', ConexionController::class)
         ->middleware('role:Administrador|Secretaria');

    Route::resource('lecturas', LecturaController::class)
         ->middleware('role:Administrador|Secretaria|Tecnico');

    // Facturas: solo CRUD parcial (excepto show y destroy)
    Route::resource('facturas', FacturaController::class)
         ->except(['show', 'create', 'store', 'edit', 'destroy'])
         ->middleware('role:Administrador|Secretaria');
         
    Route::resource('pagos', PagoController::class)
         ->middleware('role:Administrador|Secretaria');

    Route::resource('tarifas', TarifaController::class)
         ->middleware('role:Administrador');

    Route::resource('tarifasConceptos', TarifaConceptoController::class)
         ->middleware('role:Administrador');


    // --- RUTAS DE ACCIONES ESPECIALES (Admin y Secretaria) ---
    Route::middleware(['role:Administrador|Secretaria'])->group(function () {
        
        // Facturación Manual
        Route::get('/facturacion/generar', [FacturacionController::class, 'showGenerador'])
             ->name('facturacion.generar.show');

        Route::post('/facturacion/generar', [FacturacionController::class, 'storeGeneracion'])
             ->name('facturacion.generar.store');
             
        // Acciones de Factura
        Route::post('/facturas/{factura}/anular', [FacturaController::class, 'anular'])
             ->name('facturas.anular')
             ->where('factura', '[0-9]+');

        Route::get('/facturas/{factura}/pdf', [FacturaController::class, 'descargarPdf']) 
             ->name('facturas.pdf')
             ->where('factura', '[0-9]+');

        // Reclamo Tipos (CRUD manual)
        Route::post('/reclamo-tipos', [ReclamoTipoController::class, 'store'])
             ->name('reclamoTipos.store');

        Route::put('/reclamo-tipos/{reclamoTipo}', [ReclamoTipoController::class, 'update'])
             ->name('reclamoTipos.update');

        Route::delete('/reclamo-tipos/{reclamoTipo}', [ReclamoTipoController::class, 'destroy'])
             ->name('reclamoTipos.destroy');

          Route::get('/reportes', [ReporteController::class, 'index'])
               ->name('reportes.index');    

    });


    // --- RUTAS SOLO PARA ADMIN ---
    Route::middleware(['role:Administrador'])->group(function () {
        Route::put('/facturas/{factura}/update-monto', [FacturaController::class, 'updateMonto'])
             ->name('facturas.updateMonto')
             ->where('factura', '[0-9]+');
             
        Route::delete('/facturas/{factura}', [FacturaController::class, 'destroy'])
             ->name('facturas.destroy')
             ->where('factura', '[0-9]+');
    });
    

    // --- RUTAS COMPARTIDAS (Admin, Secretaria y Usuario) ---
    Route::middleware(['role:Administrador|Secretaria|Usuario'])->group(function () {
        Route::get('/facturas/{factura}', [FacturaController::class, 'show'])
             ->name('facturas.show')
             ->where('factura', '[0-9]+');

        Route::get('/reclamos/{reclamo}', [ReclamoController::class, 'show'])
             ->name('reclamos.show')
             ->whereNumber('reclamo');

        Route::get('/factura/{factura}/imprimir', [FacturaController::class, 'imprimirFactura'])
             ->name('facturas.imprimir')
             ->where('factura', '[0-9]+');


             // Rutas para el QR
     Route::post('/pagos/generar-qr', [PagoController::class, 'generarQr'])->name('pagos.generarQr');
     Route::post('/pagos/verificar-qr', [PagoController::class, 'verificarQr'])->name('pagos.verificarQr');
     });


    // --- APIS (Para Vue) ---
    Route::middleware(['role:Administrador|Secretaria|Tecnico'])->group(function () {
        Route::get('/afiliados/buscar-por-ci/{ci}', [AfiliadoController::class, 'buscarPorCI'])
             ->name('afiliados.buscarPorCI');

        Route::get('/api/lecturas/buscar-conexiones', [LecturaController::class, 'apiSearchConexiones'])
             ->name('api.conexiones.search_with_reading');

        Route::get('/api/lecturas/pendientes', [LecturaController::class, 'apiGetPendientes'])
             ->name('api.lecturas.pendientes');

        Route::get('/api/tarifas/activa', [LecturaController::class, 'apiGetTarifaActiva'])
             ->name('api.tarifas.activa');

        Route::get('/lecturas/{lectura}/aviso', [LecturaController::class, 'showAviso'])
             ->name('lecturas.aviso')
             ->where('lectura', '[0-9]+');
             
        Route::post('/zonas', [ZonaController::class, 'store'])
             ->name('zonas.store');

        Route::get('/reclamos', [ReclamoController::class, 'index'])
             ->name('reclamos.index');

        Route::put('/reclamos/{reclamo}', [ReclamoController::class, 'update'])
             ->name('reclamos.update')
             ->whereNumber('reclamo');

        Route::put('/reclamos/{reclamo}/reabrir', [ReclamoController::class, 'reabrir'])
             ->name('reclamos.reabrir')
             ->whereNumber('reclamo');
    });


    // ================================
    // RUTAS PARA USUARIO (Rol 'Usuario')
    // ================================
    Route::middleware(['role:Usuario'])->group(function () {
        
        Route::get('/mi-cuenta', [FacturaController::class, 'misFacturas'])
             ->name('mi.cuenta');
        
        Route::get('/mi-historial-pagos', [PagoController::class, 'miHistorial'])
             ->name('pagos.mihistorial'); 
             
     //     Route::get('/factura/{factura}/imprimir', [FacturaController::class, 'imprimirFactura'])
     //          ->name('facturas.imprimir.usuario')
     //          ->where('factura', '[0-9]+');

        Route::get('/mis-reclamos', [ReclamoController::class, 'usuarioIndex'])
             ->name('reclamos.usuarioIndex');

        Route::get('/reclamos/nuevo', [ReclamoController::class, 'create'])
             ->name('reclamos.create');

        Route::post('/reclamos', [ReclamoController::class, 'store'])
             ->name('reclamos.store');

        Route::get('/pendiente-habilitacion', [UsuarioDashboardController::class, 'pendiente'])
             ->name('usuario.pendiente');
    });            

    route::get('/buscar', [BuscadorController::class, 'buscar'])
         ->name('buscar');

});

Route::get('/hit-view', function () {
    $file = storage_path('app/views.json');
    $url  = request('url') ?: request()->path() ?: '/';

    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    $data[$url] = ($data[$url] ?? 0) + 1;
    file_put_contents($file, json_encode($data));

    return ['views' => $data[$url]];
})->name('hit-view');

Route::post('/webhook-pago', [PagoController::class, 'callbackPagoFacil']);



// Route::get('/diagnostico-banco', function () {
//     // 1. Autenticar 
//     $login = Http::withHeaders([
//         'tcTokenService' => env('PAGO_FACIL_SERVICE'),
//         'tcTokenSecret'  => env('PAGO_FACIL_SECRET')
//     ])->post('https://masterqr.pagofacil.com.bo/api/services/v2/login');

//     if ($login->failed()) return "Error Login: " . $login->body();
    
//     // Si el login falla y no devuelve values, mostramos el error completo
//     $jsonLogin = $login->json();
//     if (!isset($jsonLogin['values']['accessToken'])) {
//         return "Login rechazado por el banco: " . $login->body();
//     }

//     $token = $jsonLogin['values']['accessToken'];

//     // 2. Ver Servicios Habilitados
//     $servicios = Http::withHeaders([
//         'Authorization' => 'Bearer ' . $token
//     ])->post('https://masterqr.pagofacil.com.bo/api/services/v2/list-enabled-services');

//     return $servicios->json();
// });