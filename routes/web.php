<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Spatie\Permission\Facades\Permission;


// Controladores de Dashboard
use App\Http\Controllers\{
    AdminDashboardController,
    SecretariaDashboardController,
    TecnicoDashboardController,
    UsuarioDashboardController
};

// Otros controladores
use App\Http\Controllers\{
    UsuarioController,
    SocioController,
    ConexionController,
    LecturaController,
    FacturaController,
    PagoController,
    IngresoEgresoController,
    AccesoAutorizadoController,
    ReporteController,
    AuditoriaController,
    NotificacionController
};

// ================================
// Página de bienvenida
// ================================
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// ================================
// Redirección inicial: /dashboard → según rol
// ================================
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->get('/dashboard', function () {
    $user = auth()->user();

    if ($user->hasRole('Administrador')) {
        return redirect()->route('admin.dashboard');
    } elseif ($user->hasRole('Secretaria')) {
        return redirect()->route('secretaria.dashboard');
    } elseif ($user->hasRole('Tecnico')) {
        return redirect()->route('tecnico.dashboard');
    } else {
        return redirect()->route('usuario.dashboard');
    }
})->name('dashboard');

// ================================
// DASHBOARDS (usando controladores para cargar datos)
// ================================
Route::middleware(['auth', 'role:Administrador'])
    ->get('/admin/dashboard', [AdminDashboardController::class, 'index'])
    ->name('admin.dashboard');

Route::middleware(['auth', 'role:Secretaria'])
    ->get('/secretaria/dashboard', [SecretariaDashboardController::class, 'index'])
    ->name('secretaria.dashboard');

Route::middleware(['auth', 'role:Tecnico'])
    ->get('/tecnico/dashboard', [TecnicoDashboardController::class, 'index'])
    ->name('tecnico.dashboard');

Route::middleware(['auth', 'role:Usuario'])
    ->get('/usuario/dashboard', [UsuarioDashboardController::class, 'index'])
    ->name('usuario.dashboard');

// ================================
// RUTAS PARA ADMINISTRADOR
// ================================
Route::middleware(['auth', 'role:Administrador'])->group(function () {
    Route::resource('usuarios', UsuarioController::class);
    Route::resource('socios', SocioController::class);
    Route::resource('ingresos-egresos', IngresoEgresoController::class);
    Route::resource('accesos-autorizados', AccesoAutorizadoController::class);
    Route::resource('facturas', FacturaController::class);
    Route::resource('pagos', PagoController::class);
    Route::resource('conexiones', ConexionController::class);
    Route::resource('lecturas', LecturaController::class);
    Route::resource('reportes', ReporteController::class);
    Route::get('auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
});

// ================================
// RUTAS PARA SECRETARIA
// ================================
Route::middleware(['auth', 'role:Secretaria'])->group(function () {
    Route::resource('socio', SocioController::class)->names('secretaria.socios');
    Route::resource('factura', FacturaController::class);
    Route::resource('pago', PagoController::class);
    Route::resource('accesos-autorizados', AccesoAutorizadoController::class);
    Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
});

// ================================
// RUTAS PARA TÉCNICO
// ================================
Route::middleware(['auth', 'role:Tecnico'])->group(function () {
    Route::resource('conexiones', ConexionController::class);
    Route::resource('lecturas', LecturaController::class);
    Route::get('socios/{id}', [SocioController::class, 'show'])->name('socios.show');
});

// ================================
// RUTAS PARA USUARIO
// ================================
Route::middleware(['auth', 'role:Usuario'])->group(function () {
    Route::get('/mi-cuenta', [FacturaController::class, 'misFacturas'])->name('mi.cuenta');
    Route::get('/notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::get('/factura/{id}/descargar', [FacturaController::class, 'descargarPdf'])->name('factura.descargar');
});

