<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('requisitos', function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->string("nombre", 255);
            $table->boolean("es_para_todos")->default(false);
            $table->boolean("es_para_propietario")->default(false);
            $table->boolean("es_para_compra_venta")->default(false);
            $table->boolean("es_para_posesion")->default(false);
            $table->text("descripcion")->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisitos');
    }
};
