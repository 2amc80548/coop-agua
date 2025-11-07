<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('afiliado_requisito', function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->bigInteger("afiliado_id") /* was UNSIGNED */;
            $table->bigInteger("requisito_id") /* was UNSIGNED */;
            $table->date("fecha_entrega")->nullable();
            $table->string("observacion", 255)->nullable();
            $table->timestamp("created_at")->nullable()->useCurrent();
            $table->timestamp("updated_at")->nullable()->useCurrent() /* TODO: emulate ON UPDATE CURRENT_TIMESTAMP with trigger in Postgres */;
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afiliado_requisito');
    }
};
