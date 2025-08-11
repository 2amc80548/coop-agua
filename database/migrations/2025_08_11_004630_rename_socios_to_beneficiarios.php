<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) eliminar FK que apunta a 'socios' desde 'conexiones' (si existe)
        if (Schema::hasTable('conexiones')) {
            Schema::table('conexiones', function (Blueprint $table) {
                if (Schema::hasColumn('conexiones', 'socio_id')) {
                    // intenta soltar la FK por columna (funciona con convenciones)
                    $sm = Schema::getConnection()->getDoctrineSchemaManager();
                    // simple drop: 
                    $table->dropForeign(['socio_id']);
                }
            });
        }

        // 2) renombrar tabla (si existe)
        if (Schema::hasTable('socios')) {
            Schema::rename('socios', 'beneficiarios');
        }

        // 3) volver a crear FK en 'conexiones' pero apuntando a 'beneficiarios'
        if (Schema::hasTable('conexiones')) {
            Schema::table('conexiones', function (Blueprint $table) {
                if (Schema::hasColumn('conexiones', 'socio_id')) {
                    $table->foreign('socio_id')->references('id')->on('beneficiarios')->onDelete('cascade');
                }
            });
        }
    }

    public function down(): void
    {
        // revertir: quitar FK y renombrar de vuelta
        if (Schema::hasTable('conexiones')) {
            Schema::table('conexiones', function (Blueprint $table) {
                if (Schema::hasColumn('conexiones', 'socio_id')) {
                    $table->dropForeign(['socio_id']);
                }
            });
        }

        if (Schema::hasTable('beneficiarios')) {
            Schema::rename('beneficiarios', 'socios');
        }

        if (Schema::hasTable('conexiones')) {
            Schema::table('conexiones', function (Blueprint $table) {
                if (Schema::hasColumn('conexiones', 'socio_id')) {
                    $table->foreign('socio_id')->references('id')->on('socios')->onDelete('cascade');
                }
            });
        }
    }
};

