<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatosInicialesSeeder extends Seeder
{
    public function run(): void
    {
        // --- REQUISITOS ---
        DB::table('requisitos')->truncate();
        DB::table('requisitos')->insert([
            ['id' => 1, 'nombre' => 'Fotocopia CI', 'es_para_todos' => 1, 'es_para_propietario' => 0, 'es_para_compra_venta' => 0, 'es_para_posesion' => 0, 'descripcion' => null],
            ['id' => 2, 'nombre' => 'Plano de Ubicación', 'es_para_todos' => 0, 'es_para_propietario' => 1, 'es_para_compra_venta' => 1, 'es_para_posesion' => 0, 'descripcion' => null],
            ['id' => 3, 'nombre' => 'Documento Compra/Venta', 'es_para_todos' => 0, 'es_para_propietario' => 0, 'es_para_compra_venta' => 1, 'es_para_posesion' => 0, 'descripcion' => null],
            ['id' => 4, 'nombre' => 'Croquis', 'es_para_todos' => 0, 'es_para_propietario' => 0, 'es_para_compra_venta' => 0, 'es_para_posesion' => 1, 'descripcion' => null],
            ['id' => 5, 'nombre' => 'Certificación OTB', 'es_para_todos' => 0, 'es_para_propietario' => 0, 'es_para_compra_venta' => 0, 'es_para_posesion' => 1, 'descripcion' => null],
        ]);

        // --- ZONAS ---
        DB::table('zonas')->truncate();
        DB::table('zonas')->insert([
            ['id' => 2, 'nombre' => 'B/ CARACORE'],
            ['id' => 1, 'nombre' => 'B/ CARMEN'],
            ['id' => 4, 'nombre' => 'B/ COMERCIAL'],
            ['id' => 3, 'nombre' => 'B/ FERROVIARIO'],
            ['id' => 5, 'nombre' => 'B/ SANTA CRUZ'],
            ['id' => 11, 'nombre' => 'GUTIERREZ'],
            ['id' => 6, 'nombre' => 'SANTA CRUZ DE LA SIERRA'],
        ]);

        // --- TARIFAS ---
        DB::table('tarifas')->truncate();
        DB::table('tarifas')->insert([
            [
                'id' => 2,
                'vigente_desde' => '2025-10-28',
                'vigente_hasta' => '2025-10-31',
                'activo' => 1,
                'min_m3' => 7,
                'min_monto' => 24.50,
                'precio_m3' => 3.50,
                'descuento_adulto_mayor_pct' => 20.00,
                'afiliacion_socio_monto' => 200.00,
                'afiliacion_usuario_monto' => 0.00,
                'multa_corte_monto' => 30.00,
                'cisterna_10k_monto' => 80.00,
                'notas' => null,
                'created_at' => '2025-10-16 08:55:42',
                'updated_at' => '2025-10-28 08:38:17',
            ]
        ]);

        // --- TIPOS DE RECLAMOS ---
        DB::table('reclamo_tipos')->truncate();
        DB::table('reclamo_tipos')->insert([
            ['id' => 5, 'nombre' => 'Atención al Cliente / Trato'],
            ['id' => 1, 'nombre' => 'Facturación Incorrecta'],
            ['id' => 3, 'nombre' => 'Fuga de Agua / Problema Técnico'],
            ['id' => 6, 'nombre' => 'Otros'],
            ['id' => 2, 'nombre' => 'Problema con la Lectura'],
            ['id' => 4, 'nombre' => 'Problema de Suministro (Corte)'],
            ['id' => 7, 'nombre' => 'PRUEBA1'],
        ]);
    }
}
