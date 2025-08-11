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
        Schema::create('lecturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conexion_id')->constrained('conexiones')->onDelete('cascade'); // Relación con conexión
            $table->date('fecha_lectura'); // Fecha de lectura
            $table->decimal('lectura_anterior', 8, 2)->default(0); // Lectura anterior
            $table->decimal('lectura_actual', 8, 2); // Lectura actual
            $table->string('observacion')->nullable(); // Nota opcional
            $table->foreignId('registrado_por')->constrained('users')->onDelete('cascade'); // Técnico que registró
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('lecturas');
    }
    
};
