<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Tarifa;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TarifaLogicTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prueba que Tarifa::vigente() devuelva la tarifa activa correcta.
     */
    public function test_tarifa_vigente_returns_active_rate(): void
    {
        $tarifa = Tarifa::create([
            'tipo_conexion' => 'domiciliaria',
            'vigente_desde' => '2026-01-01',
            'activo' => true,
            'min_m3' => 10,
            'min_monto' => 35.00,
            'precio_m3' => 3.50,
        ]);

        $tarifaVigente = Tarifa::vigente('2026-06-01');

        $this->assertNotNull($tarifaVigente);
        $this->assertEquals(35.00, $tarifaVigente->min_monto);
        $this->assertEquals(10, $tarifaVigente->min_m3);
    }
}
