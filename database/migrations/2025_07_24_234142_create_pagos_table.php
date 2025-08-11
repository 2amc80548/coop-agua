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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained('facturas')->onDelete('cascade'); // Relación con factura
            $table->decimal('monto_pagado', 10, 2); // Monto pagado
            $table->string('metodo_pago')->default('efectivo'); // Método de pago
            $table->date('fecha_pago'); // Fecha del pago
            $table->foreignId('registrado_por')->constrained('users')->onDelete('cascade'); // Quién registró el pago
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
    
};
