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
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conexion_id')->constrained('conexiones')->onDelete('cascade'); // Relación con conexión
            $table->foreignId('lectura_id')->constrained('lecturas')->onDelete('cascade'); // Relación con lectura
            $table->decimal('consumo_m3', 8, 2)->default(0); // Consumo en metros cúbicos
            $table->decimal('monto_total', 10, 2)->default(0); // Monto total a pagar
            $table->enum('estado', ['pendiente', 'pagado', 'parcial'])->default('pendiente'); // Estado de la factura
            $table->date('fecha_emision'); // Fecha en que se emitió
            $table->date('fecha_pago')->nullable(); // Fecha en que se pagó
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
    
};
