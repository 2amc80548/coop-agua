<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class PagoFacilService
{
    protected $baseUrl;
    protected $serviceKey;
    protected $secretKey;

    public function __construct()
    {
        $this->baseUrl = env('PAGO_FACIL_BASE_URL');
        $this->serviceKey = env('PAGO_FACIL_SERVICE');
        $this->secretKey = env('PAGO_FACIL_SECRET');
    }

    /**
     * PASO 1 DEL FLUJO: AUTENTICACIÓN
     * Obtiene el "Bearer Token" temporal
     */
/**
     * PASO 1 DEL FLUJO: AUTENTICACIÓN
     * Corrección: Enviar credenciales en HEADERS, no en el body.
     */
    private function autenticar()
    {
        try {
            // 1. Enviar credenciales en los HEADERS como pide el PDF
            $response = Http::withHeaders([
                'tcTokenService' => $this->serviceKey, // Credencial 1 en Header
                'tcTokenSecret'  => $this->secretKey   // Credencial 2 en Header
            ])->post($this->baseUrl . '/login'); // El cuerpo va vacío

            $data = $response->json();

            // 2. Verificamos si respondió éxito
            // Según el PDF, el éxito devuelve error: 0 y status: 1
            if ($response->successful() && isset($data['values']['accessToken'])) {
                return $data['values']['accessToken']; 
            }

            // Si falla, guardamos el error en el log para que sepas qué pasó
            \Illuminate\Support\Facades\Log::error('Error Auth PagoFácil:', $data ?? []);
            
            throw new Exception("El banco rechazó la conexión. Mensaje: " . ($data['message'] ?? 'Desconocido'));

        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error Guzzle: " . $e->getMessage());
            return null;
        }
    }

    /**
     * PASO 2 DEL FLUJO: GENERAR QR
     */
    /**
     * PASO 2 DEL FLUJO: GENERAR QR (CORREGIDO Y MEJORADO)
     */
  /**
     * PASO 2 DEL FLUJO: GENERAR QR (CORREGIDO CAMPO FALTANTE)
     */
    public function generarQR($factura, $afiliado)
    {
        $token = $this->autenticar();
        if (!$token) {
            return ['success' => false, 'message' => 'No se pudo autenticar con el banco.'];
        }

        $suffix = uniqid(); 
        $paymentNumber = env('PAGO_FACIL_PREFIX', 'grupo05cc') . '_' . $factura->id . '_' . $suffix;

        // Validar datos del cliente
        $emailCliente = filter_var($afiliado->email, FILTER_VALIDATE_EMAIL) ? $afiliado->email : 'amc80548@gmail.com';
        $telefonoCliente = (!empty($afiliado->celular)) ? $afiliado->celular : '77813839';

        $cuerpoSolicitud = [
            "paymentMethod" => 4, // QR BCP
            "clientName"    => substr($afiliado->nombre_completo ?? 'Cliente Generico', 0, 50),
            "documentType"  => 1, // CI
            "documentId"    => $afiliado->ci ?? '0', 
            "phoneNumber"   => $telefonoCliente,
            "email"         => $emailCliente, 
            "paymentNumber" => $paymentNumber, 
            "amount"        => (float)number_format($factura->deuda_pendiente, 2, '.', ''), 
            "currency"      => 2, // 2 = BOB (Confirmado por tu log anterior)
            "clientCode"    => (string)$afiliado->id,
            
            // --- CAMBIO IMPORTANTE: URL DE CALLBACK ---
            // Aunque estés en localhost, el banco exige este campo. 
            // Ponemos una URL dummy o la tuya local, no importa, pero debe ir.
            "callbackUrl"   => "https://aguacabezas.fun/api/webhook-pago", 
            // ------------------------------------------

            "orderDetail"   => [
                [
                    "serial" => 1,
                    "product" => "Pago Agua F-" . $factura->id,
                    "quantity" => 1,
                    "price" => (float)number_format($factura->deuda_pendiente, 2, '.', ''),
                    "discount" => 0,
                    "total" => (float)number_format($factura->deuda_pendiente, 2, '.', '')
                ]
            ]
        ];

        try {
            $response = Http::timeout(15)->withHeaders([
                'Authorization' => 'Bearer ' . $token
            ])->post($this->baseUrl . '/generate-qr', $cuerpoSolicitud);

            $resultado = $response->json();

            \Illuminate\Support\Facades\Log::info('Intento QR V2:', ['envio' => $cuerpoSolicitud, 'respuesta' => $resultado]);

            if ($response->successful() && ($resultado['error'] ?? 1) === 0) {
                return [
                    'success' => true,
                    'qr_image' => $resultado['values']['qrImage'] ?? $resultado['values']['qrBase64'],
                    'payment_number' => $paymentNumber,
                    'transaccion_id' => $resultado['values']['transactionId'] ?? null
                ];
            } else {
                $mensajeError = $resultado['message'] ?? 'Error desconocido';
                return [
                    'success' => false, 
                    'message' => "El banco respondió: " . $mensajeError
                ];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error interno: ' . $e->getMessage()];
        }
    }

    /**

     * PASO 3: CONSULTAR ESTADO (Polling) - CON LOGS
     */
    /**
     * PASO 3: CONSULTAR ESTADO (CORREGIDO)
     */
    /**
     * PASO 3: CONSULTAR ESTADO (VERSIÓN FINAL SEGURA)
     */
   public function consultarTransaccion($paymentNumber)
    {
        $token = $this->autenticar();
        if (!$token) return ['error' => true, 'message' => 'No auth'];

        try {
            // Preparamos la petición con un tiempo de espera razonable
            $http = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $token
            ]);

            // EL TRUCO DE VELOCIDAD:
            // Si estás en tu PC (local), desactivamos la verificación SSL.
            // Esto hace que la respuesta baje de 10s a 0.5s.
            // En producción (servidor real), esto se ignora automáticamente.
            if (app()->isLocal()) {
                $http->withoutVerifying();
            }

            $response = $http->post($this->baseUrl . '/query-transaction', [
                'paymentNumber' => $paymentNumber,       
                'companyTransactionId' => $paymentNumber 
            ]);

            return $response->json();

        } catch (Exception $e) {
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }
}