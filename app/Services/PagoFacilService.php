<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PagoFacilService
{
    protected $baseUrl;
    protected $serviceKey;
    protected $secretKey;

    public function __construct()
    {
        $this->baseUrl = env('PAGO_FACIL_BASE_URL', 'https://masterqr.pagofacil.com.bo/api/services/v2');
        $this->serviceKey = env('PAGO_FACIL_SERVICE');
        $this->secretKey = env('PAGO_FACIL_SECRET');
    }

    private function autenticar()
    {
        try {
            $response = Http::withHeaders([
                'tcTokenService' => $this->serviceKey,
                'tcTokenSecret'  => $this->secretKey
            ])->post($this->baseUrl . '/login');

            $data = $response->json();

            if ($response->successful() && isset($data['values']['accessToken'])) {
                return $data['values']['accessToken'];
            }

            Log::error('Error Auth PagoFácil:', $data ?? []);
            return null;

        } catch (Exception $e) {
            Log::error("Error conexión Auth: " . $e->getMessage());
            return null;
        }
    }

    public function generarQR($factura, $afiliado)
    {
        $token = $this->autenticar();
        if (!$token) {
            return ['success' => false, 'message' => 'No se pudo autenticar con el banco.'];
        }

        // Generar ID único
        $suffix = uniqid();
        $paymentNumber = env('PAGO_FACIL_PREFIX', 'grupo05cc') . '_' . $factura->id . '_' . $suffix;

        // Datos seguros del cliente (evita error si es null)
        $nombreCliente = substr($afiliado->nombre_completo ?? 'Cliente Generico', 0, 50);
        $ciCliente     = $afiliado->ci ?? '0';
        $celular       = $afiliado->celular ?? '70000000';
        $email         = (filter_var($afiliado->email ?? '', FILTER_VALIDATE_EMAIL)) ? $afiliado->email : 'sin_correo@sistema.com';

        $cuerpoSolicitud = [
            "paymentMethod" => 4,
            "clientName"    => $nombreCliente,
            "documentType"  => 1,
            "documentId"    => $ciCliente,
            "phoneNumber"   => $celular,
            "email"         => $email,
            "paymentNumber" => $paymentNumber,
            "amount"        => (float)number_format($factura->deuda_pendiente, 2, '.', ''),
            "currency"      => 2,
            "clientCode"    => (string)$afiliado->id,

            "callbackUrl"   => "https://aguacabezas.fun/api/webhook-pago", 


            "orderDetail"   => [
                [
                    "serial" => 1,
                    "product" => "Servicio F-" . $factura->id,
                    "quantity" => 1,
                    "price" => (float)number_format($factura->deuda_pendiente, 2, '.', ''),
                    "discount" => 0,
                    "total" => (float)number_format($factura->deuda_pendiente, 2, '.', '')
                ]
            ]
        ];

        try {
            $response = Http::timeout(20)->withHeaders([
                'Authorization' => 'Bearer ' . $token
            ])->post($this->baseUrl . '/generate-qr', $cuerpoSolicitud);

            $resultado = $response->json();

            // Verificación estricta basada en PDF
            if ($response->successful() && ($resultado['error'] ?? 1) === 0) {
                return [
                    'success' => true,
                    'qr_image' => $resultado['values']['qrImage'] ?? $resultado['values']['qrBase64'],
                    'payment_number' => $paymentNumber,
                    'transaccion_id' => $resultado['values']['transactionId'] ?? null
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $resultado['message'] ?? 'Error desconocido al generar QR'
                ];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Excepción: ' . $e->getMessage()];
        }
    }

    public function consultarTransaccion($paymentNumber)
    {
        $token = $this->autenticar();
        if (!$token) return ['error' => true, 'message' => 'Fallo auth en consulta'];

        try {
            $http = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $token
            ]);

            if (app()->isLocal()) {
                $http->withoutVerifying();
            }

            // OJO: PDF Pag 9 dice "company TransactionId" con espacio, pero probamos sin espacio primero.
            // Si falla, intentamos enviar solo paymentNumber que es lo que usa el ejemplo del chat.
            $response = $http->post($this->baseUrl . '/query-transaction', [
                'paymentNumber' => $paymentNumber
            ]);

            return $response->json();

        } catch (Exception $e) {
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }


    public function callbackPagoFacil(Request $request)
    {
        try {
            
            $datos = $request->all();
            $paymentNumber = $datos['PedidoID'] ?? null; 
            $estado = $datos['Estado'] ?? null; 

            // Log para depurar si llega la petición
            Log::info('Webhook PagoFácil recibido:', $datos);

            if ($paymentNumber) {
                $partes = explode('_', $paymentNumber);
                $facturaId = $partes[1] ?? null;

                if ($facturaId) {
               
                    DB::transaction(function () use ($facturaId, $paymentNumber) {
                        // Verificar si ya se pagó para no duplicar
                        if (Pago::where('referencia', $paymentNumber)->exists()) return;

                        $factura = Factura::lockForUpdate()->find($facturaId);

                        // Si la factura existe y sigue impaga, la pagamos
                        if ($factura && $factura->estado === 'impaga') {
                            Pago::create([
                                'factura_id'     => $factura->id,
                                'monto_pagado'   => $factura->deuda_pendiente,
                                'fecha_pago'     => Carbon::now(),
                                'forma_pago'     => 'QR (Webhook)',
                                'referencia'     => $paymentNumber,
                                'registrado_por' => 1, // Usuario sistema
                            ]);

                            $factura->update([
                                'estado'          => 'pagado',
                                'deuda_pendiente' => 0,
                                'fecha_pago'      => Carbon::now()
                            ]);
                        }
                    });
                }
            }

            // 2. RESPUESTA OBLIGATORIA PARA PAGO FÁCIL 
            // Si no devuelves esto, ellos creen que falló y te ponen en Estado 5
            return response()->json([
                "error" => 0,
                "status" => 1,
                "message" => "Pago recibido correctamente",
                "values" => true
            ]);

        } catch (\Exception $e) {
            Log::error('Error en Webhook: ' . $e->getMessage());
            // Aun si falla internamente, es mejor decirles que recibimos el mensaje
            // para que dejen de insistir.
            return response()->json([
                "error" => 1,
                "status" => 0,
                "message" => "Error interno: " . $e->getMessage(),
                "values" => false
            ]);
        }
    }
}