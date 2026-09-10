<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

// --- Controladores de Dashboard ---
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\SecretariaDashboardController;
use App\Http\Controllers\TecnicoDashboardController;
use App\Http\Controllers\UsuarioDashboardController;

// --- Controladores de Módulos ---
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
use App\Http\Controllers\ReporteController;

// ============================================================
// RUTA PÚBLICA: Página de Bienvenida
// ============================================================
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
    ]);
});

// ============================================================
// RUTAS AUTENTICADAS
// Todas las rutas protegidas comparten este middleware base.
// ============================================================
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->group(function () {

    // ----------------------------------------------------------
    // Redirección inteligente del /dashboard según rol
    // ----------------------------------------------------------
    Route::get('/dashboard', function () {
        $user = Auth::user();

        if ($user->hasRole('Administrador')) return redirect()->route('admin.dashboard');
        if ($user->hasRole('Secretaria'))    return redirect()->route('secretaria.dashboard');
        if ($user->hasRole('Tecnico'))       return redirect()->route('tecnico.dashboard');

        if ($user->hasRole('Usuario')) {
            return $user->afiliado_id === null
                ? redirect()->route('usuario.pendiente')
                : redirect()->route('usuario.dashboard');
        }

        // Sin rol asignado: desloguear
        Auth::logout();
        return redirect('/');
    })->name('dashboard');


    // ----------------------------------------------------------
    // DASHBOARDS (Acceso exclusivo por rol)
    // ----------------------------------------------------------
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


    // ----------------------------------------------------------
    // MÓDULOS CRUD — Administrador y Secretaria
    // ----------------------------------------------------------
    Route::middleware(['role:Administrador|Secretaria'])->group(function () {

        // Búsqueda rápida de afiliados (Typeahead) — DEBE ir antes de Route::resource('afiliados')
        Route::get('/afiliados/buscar', [AfiliadoController::class, 'apiSearch'])
            ->name('afiliados.buscar');

        Route::resource('afiliados', AfiliadoController::class);
        Route::resource('conexiones', ConexionController::class);

        // Documentos de Afiliado (Fase 2)
        Route::post('/afiliados/{afiliado}/documentos', [AfiliadoController::class, 'subirDocumento'])
            ->name('afiliados.documentos.store');
        Route::delete('/afiliados/documentos/{documento}', [AfiliadoController::class, 'eliminarDocumento'])
            ->name('afiliados.documentos.destroy');

        // Traspaso de Titularidad de Conexión (Fase 2)
        Route::post('/conexiones/{conexion}/traspasar', [AfiliadoController::class, 'traspasarTitularidad'])
            ->name('conexiones.traspasar');

        // Facturas: CRUD parcial (sin show/imprimir — acceso compartido más abajo)
        Route::resource('facturas', FacturaController::class)
            ->except(['show', 'create', 'store', 'edit', 'destroy']);

        Route::resource('pagos', PagoController::class);

        // Facturación Masiva
        Route::get('/facturacion/generar',  [FacturacionController::class, 'showGenerador'])
            ->name('facturacion.generar.show');
        Route::post('/facturacion/generar', [FacturacionController::class, 'storeGeneracion'])
            ->name('facturacion.generar.store');

        // Acciones especiales sobre Facturas
        Route::post('/facturas/{factura}/anular', [FacturaController::class, 'anular'])
            ->name('facturas.anular')
            ->where('factura', '[0-9]+');

        Route::get('/facturas/{factura}/pdf', [FacturaController::class, 'descargarPdf'])
            ->name('facturas.pdf')
            ->where('factura', '[0-9]+');

        // Reportes
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    });


    // ----------------------------------------------------------
    // MÓDULOS CRUD — Solo Administrador
    // ----------------------------------------------------------
    Route::middleware(['role:Administrador'])->group(function () {

        Route::resource('users', UserController::class);
        Route::resource('tarifas', TarifaController::class);
        Route::post('/tarifas/simular', [TarifaController::class, 'simular'])->name('tarifas.simular');
        Route::resource('tarifasConceptos', TarifaConceptoController::class);
        Route::get('/bitacora', [ActivityLogController::class, 'index'])->name('bitacora.index');

        // Modificaciones monetarias y eliminación de facturas
        Route::put('/facturas/{factura}/update-monto', [FacturaController::class, 'updateMonto'])
            ->name('facturas.updateMonto')
            ->where('factura', '[0-9]+');

        Route::delete('/facturas/{factura}', [FacturaController::class, 'destroy'])
            ->name('facturas.destroy')
            ->where('factura', '[0-9]+');
    });


    // ----------------------------------------------------------
    // LECTURAS — Administrador, Secretaria y Técnico
    // ----------------------------------------------------------
    Route::middleware(['role:Administrador|Secretaria|Tecnico'])->group(function () {

        Route::resource('lecturas', LecturaController::class);

        // APIs internas usadas por el formulario Vue de registro de lecturas
        Route::get('/api/lecturas/buscar-conexiones', [LecturaController::class, 'apiSearchConexiones'])
            ->name('api.conexiones.search_with_reading');

        Route::get('/api/lecturas/pendientes', [LecturaController::class, 'apiGetPendientes'])
            ->name('api.lecturas.pendientes');

        Route::get('/api/tarifas/activa', [LecturaController::class, 'apiGetTarifaActiva'])
            ->name('api.tarifas.activa');

        Route::get('/lecturas/{lectura}/aviso', [LecturaController::class, 'showAviso'])
            ->name('lecturas.aviso')
            ->where('lectura', '[0-9]+');

        // Zonas
        Route::post('/zonas', [ZonaController::class, 'store'])->name('zonas.store');

        // Búsqueda de afiliado por CI (para formulario de lectura)
        Route::get('/afiliados/buscar-por-ci/{ci}', [AfiliadoController::class, 'buscarPorCI'])
            ->name('afiliados.buscarPorCI');
    });


    // ----------------------------------------------------------
    // RUTAS COMPARTIDAS — Administrador, Secretaria y Usuario
    // (Acceso a su propia factura e impresión)
    // ----------------------------------------------------------
    Route::middleware(['role:Administrador|Secretaria|Usuario'])->group(function () {

        Route::get('/facturas/{factura}', [FacturaController::class, 'show'])
            ->name('facturas.show')
            ->where('factura', '[0-9]+');

        Route::get('/factura/{factura}/imprimir', [FacturaController::class, 'imprimirFactura'])
            ->name('facturas.imprimir')
            ->where('factura', '[0-9]+');

        // Pago QR: generar y verificar código QR (accesible desde la pantalla de pago)
        Route::post('/pagos/generar-qr',  [PagoController::class, 'generarQr'])->name('pagos.generarQr');
        Route::post('/pagos/verificar-qr', [PagoController::class, 'verificarQr'])->name('pagos.verificarQr');
    });


    // ----------------------------------------------------------
    // RUTAS PARA USUARIO (Rol 'Usuario' — Portal del cliente)
    // ----------------------------------------------------------
    Route::middleware(['role:Usuario'])->group(function () {

        Route::get('/mi-cuenta',            [FacturaController::class, 'misFacturas'])->name('mi.cuenta');
        Route::get('/mi-historial-pagos',   [PagoController::class, 'miHistorial'])->name('pagos.mihistorial');
        Route::get('/pendiente-habilitacion', [UsuarioDashboardController::class, 'pendiente'])
            ->name('usuario.pendiente');
    });

}); // fin del grupo auth

// ============================================================
// RUTA /hit-view — ELIMINADA (Hallazgo 3.1: Vulnerabilidad de Seguridad)
//
// Esta ruta era pública (sin autenticación) y permitía a cualquier persona
// en internet escribir datos al disco del servidor (storage/app/views.json),
// lo que representa una vulnerabilidad de Denial of Service (DoS) y
// escritura arbitraria de archivos.
//
// Si en el futuro se necesita analytics de vistas, implementar con:
// - Middleware autenticado
// - Cache (Redis/Memcached) en lugar de escritura directa a disco
// - Rate limiting con Route::middleware('throttle:60,1')
// ============================================================
