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
        $this->baseUrl    = config('services.pagofacil.base_url');
        $this->serviceKey = config('services.pagofacil.service_key');
        $this->secretKey  = config('services.pagofacil.secret_key');
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
        if (!$token) return ['success' => false, 'message' => 'No auth'];

        $suffix        = uniqid();
        $prefix        = config('services.pagofacil.prefix', 'grupo05cc');
        $paymentNumber = $prefix . '_' . $factura->id . '_' . $suffix;

        // Configuración de datos cliente con respaldos parametrizados
        $defaults      = config('services.pagofacil.defaults');
        $nombreCliente = substr($afiliado->nombre_completo ?? ($defaults['name'] ?? 'Cliente CoopAgua'), 0, 50);
        $ciCliente     = $afiliado->ci ?? ($defaults['ci'] ?? '0');
        $celular       = $afiliado->celular ?? ($defaults['phone'] ?? '70000000');
        $email         = (filter_var($afiliado->email ?? '', FILTER_VALIDATE_EMAIL))
            ? $afiliado->email
            : ($defaults['email'] ?? 'contacto@coopagua.com');

        $urlCallback = config('services.pagofacil.callback_url');

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
            "callbackUrl"   => $urlCallback,
            "orderDetail"   => [
                [
                    "serial"   => 1,
                    "product"  => "Servicio F-" . $factura->id,
                    "quantity" => 1,
                    "price"    => (float)number_format($factura->deuda_pendiente, 2, '.', ''),
                    "discount" => 0,
                    "total"    => (float)number_format($factura->deuda_pendiente, 2, '.', '')
                ]
            ]
        ];

        try {
            $response = Http::timeout(20)->withHeaders([
                'Authorization' => 'Bearer ' . $token
            ])->post($this->baseUrl . '/generate-qr', $cuerpoSolicitud);

            $resultado = $response->json();

            if ($response->successful() && ($resultado['error'] ?? 1) === 0) {
                return [
                    'success'        => true,
                    'qr_image'       => $resultado['values']['qrImage'] ?? $resultado['values']['qrBase64'],
                    'payment_number' => $paymentNumber,
                    'transaccion_id' => $resultado['values']['transactionId'] ?? null
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Banco: " . ($resultado['message'] ?? 'Error desconocido')
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

            $response = $http->post($this->baseUrl . '/query-transaction', [
                'paymentNumber' => $paymentNumber
            ]);

            return $response->json();

        } catch (Exception $e) {
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }
}