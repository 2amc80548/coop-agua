<?php

namespace App\Actions;

use App\Models\Afiliado;
use App\Models\Factura;
use App\Models\Pago;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProcesarPagoAction
 *
 * Acción invocable que centraliza el ciclo completo de registro de un pago.
 * Es la ÚNICA fuente de verdad para esta operación, eliminando la triplicación
 * que existía en PagoController::store, ::verificarQr y ::callbackPagoFacil.
 *
 * Garantías que ofrece:
 *   - Transacción atómica: todo se guarda o nada se guarda.
 *   - Bloqueo de fila (lockForUpdate): previene condiciones de carrera
 *     cuando el cajero y el webhook de PagoFácil procesan el mismo pago.
 *   - Protección anti-duplicado: verifica el estado de la factura antes
 *     de registrar el pago.
 *   - Actualización del estado financiero del afiliado al finalizar.
 */
class ProcesarPagoAction
{
    /**
     * Ejecuta el registro completo de un pago.
     *
     * @param  int         $facturaId   ID de la factura a pagar
     * @param  string      $formaPago   'Efectivo' | 'QR'
     * @param  string|null $referencia  Nro de referencia externa (ej: paymentNumber de PagoFácil)
     * @param  int|null    $usuarioId   ID del usuario que registra (null = sistema/webhook)
     *
     * @return Pago  El pago recién creado
     *
     * @throws \Illuminate\Validation\ValidationException  Si la factura ya está pagada
     * @throws \RuntimeException                           Si la factura no existe o no tiene deuda
     */
    public function execute(
        int     $facturaId,
        string  $formaPago,
        ?string $referencia  = null,
        ?int    $usuarioId   = null
    ): Pago {
        return DB::transaction(function () use ($facturaId, $formaPago, $referencia, $usuarioId) {

            // ---------------------------------------------------------------
            // 1. Obtener la factura con bloqueo pesimista de fila
            //    Esto previene que dos procesos simultáneos (cajero + webhook)
            //    puedan procesar el mismo pago al mismo tiempo.
            // ---------------------------------------------------------------
            $factura = Factura::where('id', $facturaId)
                ->lockForUpdate()
                ->first();

            if (!$factura) {
                throw new \RuntimeException("Factura #{$facturaId} no encontrada.");
            }

            // ---------------------------------------------------------------
            // 2. Verificar que la factura esté en estado que admite pago
            // ---------------------------------------------------------------
            if ($factura->estado !== Factura::ESTADO_IMPAGA) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'factura_id' => "La factura #{$facturaId} ya no está impaga. Estado actual: '{$factura->estado}'.",
                ]);
            }

            // ---------------------------------------------------------------
            // 3. Verificar que no exista ya un pago con esta referencia
            //    (anti-duplicado para el canal QR/webhook)
            // ---------------------------------------------------------------
            if ($referencia && Pago::where('referencia', $referencia)->exists()) {
                // Si ya existe el pago con esa referencia, lo devolvemos
                // sin crear uno nuevo. Esto es idempotente y seguro.
                Log::info("ProcesarPagoAction: Pago con referencia '{$referencia}' ya existe. Se omite duplicado.");
                return Pago::where('referencia', $referencia)->first();
            }

            // ---------------------------------------------------------------
            // 4. Verificar que haya deuda real pendiente
            // ---------------------------------------------------------------
            $montoAPagar = (float) $factura->deuda_pendiente;

            if ($montoAPagar <= 0) {
                throw new \RuntimeException(
                    "La factura #{$facturaId} no tiene deuda pendiente (Saldo: 0)."
                );
            }

            // ---------------------------------------------------------------
            // 5. Crear el registro de Pago
            // ---------------------------------------------------------------
            $pago = Pago::create([
                'factura_id'     => $factura->id,
                'monto_pagado'   => $montoAPagar,
                'fecha_pago'     => Carbon::now()->toDateString(),
                'forma_pago'     => $formaPago,
                'referencia'     => $referencia,
                'registrado_por' => $usuarioId ?? 1, // 1 = usuario sistema para webhooks
            ]);

            // ---------------------------------------------------------------
            // 6. Actualizar el estado de la Factura
            // ---------------------------------------------------------------
            $factura->update([
                'estado'          => Factura::ESTADO_PAGADO,
                'deuda_pendiente' => 0.00,
                'fecha_pago'      => Carbon::now()->toDateString(),
            ]);

            // ---------------------------------------------------------------
            // 7. Re-evaluar el estado financiero del Afiliado
            //    Cargamos la conexion con el afiliado_id para evitar N+1
            // ---------------------------------------------------------------
            $factura->loadMissing('conexion');

            if ($factura->conexion && $factura->conexion->afiliado_id) {
                Afiliado::verificarEstadoFinanciero($factura->conexion->afiliado_id);
            }

            return $pago;
        });
    }
}
