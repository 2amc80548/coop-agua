<?php

namespace App\Services;

use App\Models\Lectura;
use App\Models\Tarifa;
use Illuminate\Support\Facades\Log;

/**
 * TarifaCalculatorService
 *
 * Centraliza TODA la lógica de negocio para el cálculo de montos
 * de consumo de agua potable. Esta clase es la ÚNICA fuente de verdad
 * para las fórmulas tarifarias, eliminando la duplicación que existía
 * en FacturacionController, LecturaController y FacturaController.
 *
 * Reglas de negocio implementadas:
 *   1. Si el consumo <= mínimo Y el cobro mínimo es mayor al cálculo
 *      directo, se cobra el mínimo. Caso contrario, consumo × precio_m3.
 *   2. Si el afiliado está marcado como adulto mayor y la tarifa tiene
 *      un porcentaje de descuento > 0, se aplica el descuento por Ley.
 */
class TarifaCalculatorService
{
    /**
     * Calcula el monto de una factura a partir de una lectura y su tarifa vigente.
     *
     * @param  Lectura $lectura  Debe tener cargada la relación conexion.afiliado
     * @param  Tarifa  $tarifa   Tarifa activa correspondiente al tipo de conexión
     * @return array{
     *   consumo: float,
     *   monto_base: float,
     *   descuento: float,
     *   porcentaje_descuento: float,
     *   total: float
     * }
     */
    public function calcular(Lectura $lectura, Tarifa $tarifa): array
    {
        // Garantizamos que el consumo nunca sea negativo
        $consumo = max(0, (float) $lectura->lectura_actual - (float) $lectura->lectura_anterior);

        // ---------------------------------------------------------------
        // Regla 1: Cobro mínimo vs. consumo real
        // ---------------------------------------------------------------
        $calculoDirecto = $consumo * (float) $tarifa->precio_m3;

        $montoBase = ($consumo <= $tarifa->min_m3 && (float) $tarifa->min_monto > $calculoDirecto)
            ? (float) $tarifa->min_monto
            : $calculoDirecto;

        // ---------------------------------------------------------------
        // Regla 2: Descuento Adulto Mayor (Ley N° 1886 Bolivia)
        // ---------------------------------------------------------------
        $descuento          = 0.00;
        $porcentajeDescuento = (float) $tarifa->descuento_adulto_mayor_pct;

        // Accedemos al afiliado de forma segura usando el operador nullsafe
        $esAdultoMayor = $lectura->conexion?->afiliado?->adulto_mayor ?? false;

        if ($esAdultoMayor && $porcentajeDescuento > 0) {
            $descuento = round(($montoBase * $porcentajeDescuento) / 100, 2);
        }

        // El total nunca puede ser negativo
        $totalFinal = max(0, $montoBase - $descuento);

        return [
            'consumo'             => round($consumo, 2),
            'monto_base'          => round($montoBase, 2),
            'descuento'           => round($descuento, 2),
            'porcentaje_descuento' => $porcentajeDescuento,
            'total'               => round($totalFinal, 2),
        ];
    }

    /**
     * Obtiene la tarifa activa y vigente para un tipo de conexión específico.
     *
     * Primero intenta encontrar una tarifa activa para el tipo dado.
     * Si no hay ninguna, devuelve null y el llamador debe manejar el error.
     *
     * @param  string $tipoConexion  Ej: 'domiciliaria', 'comercial', 'institucional'
     * @return Tarifa|null
     */
    public function getTarifaActiva(string $tipoConexion): ?Tarifa
    {
        $tarifa = Tarifa::where('activo', 1)
            ->where('tipo_conexion', $tipoConexion)
            ->orderBy('vigente_desde', 'desc')
            ->first();

        if (!$tarifa) {
            Log::warning("TarifaCalculatorService: No se encontró tarifa activa para tipo '{$tipoConexion}'.");
        }

        return $tarifa;
    }

    /**
     * Método de conveniencia: obtiene la tarifa Y calcula el monto en un solo paso.
     * Lanza una excepción si no existe tarifa para el tipo de conexión.
     *
     * @throws \RuntimeException Si no hay tarifa activa para el tipo de conexión
     */
    public function calcularParaLectura(Lectura $lectura): array
    {
        $tipo   = $lectura->conexion?->tipo_conexion;
        $tarifa = $this->getTarifaActiva($tipo ?? '');

        if (!$tarifa) {
            throw new \RuntimeException(
                "No hay tarifa activa para el tipo de conexión: '{$tipo}'. " .
                "Configure una tarifa en el módulo de Tarifas antes de facturar."
            );
        }

        return $this->calcular($lectura, $tarifa);
    }
}
