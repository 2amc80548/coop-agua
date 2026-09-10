<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('traspasos_titularidad', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('conexion_id');
            $table->bigInteger('afiliado_anterior_id');
            $table->bigInteger('afiliado_nuevo_id');
            $table->date('fecha_traspaso');
            $table->text('motivo')->nullable();
            $table->bigInteger('registrado_por')->nullable();
            $table->timestamps();

            $table->foreign('conexion_id')->references('id')->on('conexiones')->cascadeOnDelete();
            $table->foreign('afiliado_anterior_id')->references('id')->on('afiliados')->cascadeOnDelete();
            $table->foreign('afiliado_nuevo_id')->references('id')->on('afiliados')->cascadeOnDelete();
            $table->foreign('registrado_por')->references('id')->on('users')->nullOnDelete();

            $table->index(['conexion_id', 'fecha_traspaso']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traspasos_titularidad');
    }
};
