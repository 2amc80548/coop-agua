<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('conexiones', function (Blueprint $table) {
            $table->decimal('lectura_inicial', 10, 2)->default(0.00)->after('tipo_conexion');
        });
    }

    public function down(): void
    {
        Schema::table('conexiones', function (Blueprint $table) {
            $table->dropColumn('lectura_inicial');
        });
    }
};
