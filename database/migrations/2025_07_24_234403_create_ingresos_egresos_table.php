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
    Schema::create('ingresos_egresos', function (Blueprint $table) {
        $table->id();
        $table->enum('tipo', ['ingreso', 'egreso']); // Tipo de movimiento
        $table->string('categoria'); // Categoría del movimiento
        $table->string('descripcion')->nullable(); // Detalle opcional
        $table->decimal('monto', 10, 2); // Monto del movimiento
        $table->date('fecha'); // Fecha del movimiento
        $table->foreignId('registrado_por')->constrained('users')->onDelete('cascade'); // Quién lo registró
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('ingresos_egresos');
}

};
