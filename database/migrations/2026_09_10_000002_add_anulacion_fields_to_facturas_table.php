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
        Schema::table('facturas', function (Blueprint $table) {
            $table->string('motivo_anulacion', 255)->nullable()->after('justificacion_modificacion');
            $table->foreignId('anulado_por')->nullable()->constrained('users')->onDelete('set null')->after('motivo_anulacion');
            $table->timestamp('fecha_anulacion')->nullable()->after('anulado_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropForeign(['anulado_por']);
            $table->dropColumn(['motivo_anulacion', 'anulado_por', 'fecha_anulacion']);
        });
    }
};
