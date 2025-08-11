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
            if (Schema::hasColumn('users', 'rol')) {
           
            }
            if (Schema::hasColumn('users', 'nombre_completo')) {
           
            }
        });
    }
    
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nombre_completo')->nullable();
            $table->enum('rol', ['administrador', 'secretaria', 'tecnico', 'usuario'])->default('secretaria');
        });
    }
    
};
