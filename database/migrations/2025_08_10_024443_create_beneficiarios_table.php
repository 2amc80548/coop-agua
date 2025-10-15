<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('beneficiarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_completo');
            $table->string('ci')->unique();
            $table->string('telefono')->nullable();
            $table->string('direccion');
            $table->string('tipo')->default('usuario'); // Campo nuevo para tipo (usuario/beneficiario)
            $table->unsignedBigInteger('user_id')->nullable(); // FK para el usuario que se cree
            $table->timestamps();
    
            // Si quieres agregar FK con tabla users:
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('beneficiarios');
    }
    
};
