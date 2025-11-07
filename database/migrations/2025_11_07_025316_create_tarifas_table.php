<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tarifas', function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->date("vigente_desde");
            $table->date("vigente_hasta")->nullable();
            $table->boolean("activo")->default(true);
            $table->integer("min_m3")->default(7);
            $table->decimal("min_monto", 10, 2)->default(24.50);
            $table->decimal("precio_m3", 10, 2)->default(3.50);
            $table->decimal("descuento_adulto_mayor_pct", 5, 2)->default(20.00);
            $table->decimal("afiliacion_socio_monto", 10, 2)->default(200.00);
            $table->decimal("afiliacion_usuario_monto", 10, 2)->default(0.00);
            $table->decimal("multa_corte_monto", 10, 2)->default(30.00);
            $table->decimal("cisterna_10k_monto", 10, 2)->default(80.00);
            $table->string("notas", 255)->nullable();
            $table->timestamp("created_at")->nullable();
            $table->timestamp("updated_at")->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifas');
    }
};
