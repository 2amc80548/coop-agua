<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->bigInteger("conexion_id") /* was UNSIGNED */;
            $table->bigInteger("lectura_id") /* was UNSIGNED */;
            $table->char("periodo", 7)->nullable();
            $table->decimal("consumo_m3", 8, 2)->default(0.00);
            $table->decimal("monto_total", 10, 2)->default(0.00);
            $table->decimal("deuda_pendiente", 10, 2)->default(0.00);
            $table->string("estado")->comment("TODO: was ENUM in MySQL");
            $table->date("fecha_emision");
            $table->date("fecha_vencimiento")->nullable();
            $table->date("fecha_pago")->nullable();
            $table->text("justificacion_modificacion")->nullable();
            $table->timestamp("created_at")->nullable();
            $table->timestamp("updated_at")->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
