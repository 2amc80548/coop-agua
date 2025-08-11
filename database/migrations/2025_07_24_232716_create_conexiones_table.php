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
        Schema::create('conexiones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_medidor')->unique(); // Código único del medidor
            $table->foreignId('socio_id')->constrained('socios')->onDelete('cascade'); // Relación con socio
            $table->enum('estado', ['activo', 'suspendido', 'eliminado'])->default('activo'); // Estado de la conexión
            $table->string('direccion'); // Dirección de la conexión (puede diferir del socio)
            $table->string('zona')->nullable(); // Zona opcional
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('conexiones');
    }
    
};
