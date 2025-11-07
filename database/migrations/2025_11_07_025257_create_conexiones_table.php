<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('conexiones', function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->string("codigo_medidor", 255);
            $table->bigInteger("afiliado_id")->nullable() /* was UNSIGNED */;
            $table->string("estado")->comment("TODO: was ENUM in MySQL");
            $table->string("direccion", 255);
            $table->bigInteger("zona_id")->nullable() /* was UNSIGNED */;
            $table->date("fecha_instalacion")->nullable();
            $table->string("tipo_conexion")->comment("TODO: was ENUM in MySQL");
            $table->timestamp("created_at")->nullable();
            $table->timestamp("updated_at")->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conexiones');
    }
};
