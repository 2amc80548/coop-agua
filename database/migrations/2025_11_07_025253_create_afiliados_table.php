<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('afiliados', function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->string("nombre_completo", 255);
            $table->string("ci", 255);
            $table->string("celular", 20)->nullable();
            $table->string("direccion", 255);
            $table->string("tipo", 100)->default("usuario");
            $table->string("estado", 20)->default("activo");
            $table->string("estado_servicio")->comment("TODO: was ENUM in MySQL");
            $table->date("fecha_afiliacion")->nullable();
            $table->date("fecha_baja")->nullable();
            $table->string("codigo", 50)->nullable();
            $table->bigInteger("zona_id")->nullable() /* was UNSIGNED */;
            $table->string("tenencia", 20)->default("propietario");
            $table->timestamp("created_at")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->boolean("adulto_mayor")->default(false);
            $table->string("profile_photo_path", 2048)->nullable();
            //$table->string("observacion", 255)->nullable(); 

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afiliados');
    }
};
