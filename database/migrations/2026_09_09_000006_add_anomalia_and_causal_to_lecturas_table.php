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
        Schema::table('lecturas', function (Blueprint $table) {
            $table->string('anomalia', 50)->default('ninguna')->after('observacion');
            $table->string('causal_inconveniente', 50)->nullable()->after('anomalia');
            $table->boolean('es_estimada')->default(false)->after('causal_inconveniente');
            $table->decimal('promedio_historico', 10, 2)->nullable()->after('es_estimada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lecturas', function (Blueprint $table) {
            $table->dropColumn(['anomalia', 'causal_inconveniente', 'es_estimada', 'promedio_historico']);
        });
    }
};
