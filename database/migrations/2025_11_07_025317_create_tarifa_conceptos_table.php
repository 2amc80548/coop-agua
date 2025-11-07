<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tarifa_conceptos', function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->bigInteger("tarifa_id") /* was UNSIGNED */;
            $table->string("codigo", 50);
            $table->string("nombre", 100);
            $table->string("tipo")->comment("TODO: was ENUM in MySQL");
            $table->decimal("valor", 10, 2)->default(0.00);
            $table->string("aplica_sobre")->comment("TODO: was ENUM in MySQL");
            $table->boolean("activo")->default(true);
            $table->timestamp("created_at")->nullable();
            $table->timestamp("updated_at")->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifa_conceptos');
    }
};
