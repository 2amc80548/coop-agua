<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reclamos', function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->bigInteger("afiliado_id") /* was UNSIGNED */;
            $table->bigInteger("user_id") /* was UNSIGNED */;
            $table->bigInteger("reclamo_tipo_id")->nullable() /* was UNSIGNED */;
            $table->string("asunto", 255);
            $table->text("mensaje_usuario");
            $table->string("estado")->comment("TODO: was ENUM in MySQL");
            $table->text("respuesta_admin")->nullable();
            $table->bigInteger("resuelto_por_user_id")->nullable() /* was UNSIGNED */;
            $table->timestamp("created_at")->nullable();
            $table->timestamp("updated_at")->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamos');
    }
};
