<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Prevenir carga perezosa de relaciones Eloquent (N+1) en desarrollo/pruebas
        Model::preventLazyLoading(! $this->app->isProduction());

        // Prohibir comandos destructivos (como db:wipe o migrate:fresh) en producción
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }
}
