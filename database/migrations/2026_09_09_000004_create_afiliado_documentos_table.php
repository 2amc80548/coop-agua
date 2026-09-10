<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('afiliado_documentos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('afiliado_id');
            $table->string('tipo_documento', 50)->comment('CI, Titulo_Propiedad, Croquis_Ubicacion, Contrato, Otro');
            $table->string('nombre_archivo', 255);
            $table->string('archivo_path', 500);
            $table->text('observacion')->nullable();
            $table->bigInteger('subido_por')->nullable();
            $table->timestamps();

            $table->foreign('afiliado_id')->references('id')->on('afiliados')->cascadeOnDelete();
            $table->foreign('subido_por')->references('id')->on('users')->nullOnDelete();

            $table->index(['afiliado_id', 'tipo_documento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afiliado_documentos');
    }
};
