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
        Schema::table('users', function (Blueprint $table) {
            // Renombramos columnas si es necesario
            $table->string('nombre_completo')->after('id'); 
            $table->string('ci')->unique()->after('nombre_completo'); 
            $table->string('telefono')->nullable()->after('ci'); 
            $table->enum('rol', ['administrador', 'secretaria', 'tecnico'])->default('secretaria')->after('telefono');
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nombre_completo', 'ci', 'telefono', 'rol']);
        });
    }
    
};
