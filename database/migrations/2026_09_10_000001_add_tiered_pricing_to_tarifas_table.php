<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tarifas', function (Blueprint $table) {
            $table->integer('bloque2_limite_m3')->default(25)->after('precio_m3');
            $table->decimal('bloque2_precio_m3', 10, 2)->default(0)->after('bloque2_limite_m3');
            $table->decimal('bloque3_precio_m3', 10, 2)->default(0)->after('bloque2_precio_m3');
            $table->integer('descuento_ley_m3_limite')->default(15)->after('descuento_adulto_mayor_pct');
            $table->decimal('cargo_fijo_mantenimiento', 10, 2)->default(0)->after('descuento_ley_m3_limite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tarifas', function (Blueprint $table) {
            $table->dropColumn([
                'bloque2_limite_m3',
                'bloque2_precio_m3',
                'bloque3_precio_m3',
                'descuento_ley_m3_limite',
                'cargo_fijo_mantenimiento',
            ]);
        });
    }
};
