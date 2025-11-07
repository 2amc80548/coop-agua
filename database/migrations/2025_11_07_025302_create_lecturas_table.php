<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lecturas', function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->bigInteger("conexion_id") /* was UNSIGNED */;
            $table->date("fecha_lectura");
            $table->char("periodo", 7);
            $table->decimal("lectura_anterior", 8, 2)->default(0.00);
            $table->decimal("lectura_actual", 8, 2);
            $table->string("observacion", 255)->nullable();
            $table->bigInteger("registrado_por")->nullable() /* was UNSIGNED */;
            $table->timestamp("created_at")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->string("estado")->comment("TODO: was ENUM in MySQL");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturas');
    }
};
