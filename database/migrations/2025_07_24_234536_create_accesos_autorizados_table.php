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
        Schema::create('accesos_autorizados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conexion_id')->constrained('conexiones')->onDelete('cascade'); // Relación con conexión
            $table->string('ci_autorizado'); // CI de la persona autorizada
            $table->string('nombre'); // Nombre del autorizado
            $table->date('fecha_registro'); // Fecha en que se dio acceso
            $table->boolean('activo')->default(true); // Si el acceso sigue activo
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('accesos_autorizados');
    }
    
};
