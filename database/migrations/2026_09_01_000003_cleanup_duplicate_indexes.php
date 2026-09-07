<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 1 - Fase 1.C: Limpieza de Índices Únicos Duplicados
 *
 * Elimina el índice único duplicado detectado en la auditoría:
 * - 'afiliados_ci_unique' y 'afiliados_uniq_afiliados_ci_unique'
 *   apuntan a la misma columna 'ci' en la tabla afiliados.
 *
 * También corrige las dos claves foráneas duplicadas sobre
 * lecturas(conexion_id) que generan carga innecesaria en el motor.
 *
 * NOTA: Se ejecuta DESPUÉS de las migraciones anteriores. No toca
 * datos, solo modifica la estructura de índices.
 */
return new class extends Migration {
    public function up(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
            Schema::table('afiliados', function (Blueprint $table) {
                try {
                    $table->dropUnique('afiliados_uniq_afiliados_ci_unique');
                } catch (\Exception $e) {
                    // Si no existe, continuamos sin lanzar error
                }
            });

            Schema::table('lecturas', function (Blueprint $table) {
                try {
                    $table->dropForeign('fk_lecturas_conexion');
                } catch (\Exception $e) {
                    // No existe en este entorno, continuamos
                }
            });
        }
    }

    public function down(): void
    {
        // Re-crear el índice duplicado si se hace rollback
        Schema::table('afiliados', function (Blueprint $table) {
            try {
                $table->unique(['ci'], 'afiliados_uniq_afiliados_ci_unique');
            } catch (\Exception $e) {
                // Ya existe
            }
        });

        // Re-crear la FK eliminada
        Schema::table('lecturas', function (Blueprint $table) {
            try {
                $table->foreign('conexion_id', 'fk_lecturas_conexion')
                    ->references('id')->on('conexiones')->onUpdate('cascade');
            } catch (\Exception $e) {
                // Ya existe
            }
        });
    }
};
