<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 1 - Fase 1.B: Corrección de Índices en Lecturas y Pagos
 *
 * Agrega los índices de rendimiento faltantes en lecturas y pagos
 * (Hallazgo 4.2 de la auditoría) para eliminar Full Table Scans
 * en los filtros y búsquedas más frecuentes del sistema.
 */
return new class extends Migration {
    public function up(): void
    {
        // Índices en tabla 'lecturas'
        // Las secretarias y técnicos filtran constantemente por periodo y estado
        Schema::table('lecturas', function (Blueprint $table) {
            $table->index(['estado', 'periodo'], 'lecturas_estado_periodo_index');
            $table->index(['fecha_lectura'], 'lecturas_fecha_lectura_index');
        });

        // Índices en tabla 'pagos'
        // Los reportes filtran pagos por fecha y forma de pago frecuentemente
        Schema::table('pagos', function (Blueprint $table) {
            $table->index(['fecha_pago'], 'pagos_fecha_pago_index');
            $table->index(['forma_pago'], 'pagos_forma_pago_index');
        });

        // Índice adicional en 'conexiones' para filtros por tipo y estado
        Schema::table('conexiones', function (Blueprint $table) {
            $table->index(['estado', 'tipo_conexion'], 'conexiones_estado_tipo_index');
        });
    }

    public function down(): void
    {
        Schema::table('lecturas', function (Blueprint $table) {
            $table->dropIndex('lecturas_estado_periodo_index');
            $table->dropIndex('lecturas_fecha_lectura_index');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropIndex('pagos_fecha_pago_index');
            $table->dropIndex('pagos_forma_pago_index');
        });

        Schema::table('conexiones', function (Blueprint $table) {
            $table->dropIndex('conexiones_estado_tipo_index');
        });
    }
};
