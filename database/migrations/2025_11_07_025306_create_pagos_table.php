<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->bigInteger("factura_id") /* was UNSIGNED */;
            $table->decimal("monto_pagado", 10, 2);
            $table->string("forma_pago", 50)->default("Efectivo");
            $table->string("referencia", 255)->nullable();
            $table->date("fecha_pago");
            $table->bigInteger("registrado_por")->nullable() /* was UNSIGNED */;
            $table->timestamp("created_at")->nullable();
            $table->timestamp("updated_at")->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
