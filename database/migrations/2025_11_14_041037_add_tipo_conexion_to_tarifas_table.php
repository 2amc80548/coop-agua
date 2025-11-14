<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('tarifas', function (Blueprint $table) {
            $table->string('tipo_conexion')
                  ->default('domiciliaria')
                  ->after('activo');
        });
    }

    public function down()
    {
        Schema::table('tarifas', function (Blueprint $table) {
            $table->dropColumn('tipo_conexion');
        });
    }
};

