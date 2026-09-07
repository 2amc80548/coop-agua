<?php

namespace Tests\Unit;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    /**
     * Verificar que el endpoint de salud responda 200 OK y estructura JSON correcta.
     */
    public function test_health_check_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'database',
                     'timestamp',
                     'environment',
                     'app_name',
                 ])
                 ->assertJson([
                     'status' => 'ok',
                     'database' => 'connected',
                 ]);
    }
}
