<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lectura;
use App\Models\Factura;
use App\Services\TarifaCalculatorService;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Afiliado;

class FacturacionController extends Controller
{
    /**
     * Inyectamos el servicio de cálculo de tarifas por constructor.
     * Laravel lo resuelve automáticamente gracias al Service Container.
     */
    public function __construct(
        protected TarifaCalculatorService $tarifaService
    ) {}

    /**
     * Muestra la página para generar facturas.
     * Carga las lecturas pendientes de un período específico con sus montos estimados.
     */
    public function showGenerador(Request $request)
    {
        $filtroPeriodo = $request->input('periodo');

        $queryPeriodos = Lectura::where('estado', 'pendiente')
            ->select(DB::raw('DISTINCT periodo'))
            ->orderBy('periodo', 'desc');

        if (!$filtroPeriodo) {
            $filtroPeriodo = $queryPeriodos->first()?->periodo;
        }

        $periodosDisponibles = $queryPeriodos->pluck('periodo');

        $lecturasPendientes = [];

        if ($filtroPeriodo) {
            $lecturasPendientes = Lectura::with([
                'conexion:id,codigo_medidor,afiliado_id,direccion,tipo_conexion',
                'conexion.afiliado:id,nombre_completo,ci,adulto_mayor',
            ])
                ->where('estado', 'pendiente')
                ->where('periodo', $filtroPeriodo)
                ->get()
                ->map(function (Lectura $lectura) {
                    try {
                        // El servicio lanza excepción si no hay tarifa
                        $calculo = $this->tarifaService->calcularParaLectura($lectura);

                        $lectura->consumo_calculado  = $calculo['consumo'];
                        $lectura->monto_estimado     = $calculo['total'];
                        $lectura->descuento_aplicado = $calculo['descuento'];
                        $lectura->error_tarifa       = null;
                    } catch (\RuntimeException $e) {
                        $lectura->consumo_calculado  = (float) $lectura->lectura_actual - (float) $lectura->lectura_anterior;
                        $lectura->monto_estimado     = 0;
                        $lectura->descuento_aplicado = 0;
                        $lectura->error_tarifa       = $e->getMessage();
                    }

                    return $lectura;
                });
        }

        return Inertia::render('Facturacion/Generar', [
            'lecturasPendientes'  => $lecturasPendientes,
            'periodosDisponibles' => $periodosDisponibles,
            'filters'             => ['periodo' => $filtroPeriodo],
        ]);
    }

    /**
     * Procesa las lecturas seleccionadas y genera las facturas de forma masiva.
     *
     * OPTIMIZACIÓN (Hallazgo 1.3): Afiliado::verificarEstadoFinanciero ya no se
     * llama dentro del bucle. Los IDs de afiliados afectados se acumulan y se
     * procesan DESPUÉS de completar la transacción, en un solo pase.
     */
    public function storeGeneracion(Request $request)
    {
        $validated = $request->validate([
            'lectura_ids'   => 'required|array|min:1',
            'lectura_ids.*' => 'integer|exists:lecturas,id',
        ]);

        $lecturaIds    = $validated['lectura_ids'];
        $generadasCount = 0;
        $afiliadosAfectados = []; // Acumulamos IDs para procesar al final

        try {
            DB::transaction(function () use ($lecturaIds, &$generadasCount, &$afiliadosAfectados) {

                $lecturasParaProcesar = Lectura::with('conexion.afiliado')
                    ->whereIn('id', $lecturaIds)
                    ->where('estado', 'pendiente')
                    ->get();

                foreach ($lecturasParaProcesar as $lectura) {
                    // El servicio lanza excepción si no hay tarifa → rollback automático
                    $calculo = $this->tarifaService->calcularParaLectura($lectura);

                    $fechaEmision    = Carbon::now();
                    $fechaVencimiento = $fechaEmision->copy()->addDays(30);

                    Factura::create([
                        'conexion_id'              => $lectura->conexion_id,
                        'lectura_id'               => $lectura->id,
                        'periodo'                  => $lectura->periodo,
                        'fecha_emision'            => $fechaEmision,
                        'fecha_vencimiento'        => $fechaVencimiento,
                        'consumo_m3'               => $calculo['consumo'],
                        'monto_total'              => $calculo['total'],
                        'deuda_pendiente'          => $calculo['total'],
                        'estado'                   => Factura::ESTADO_IMPAGA,
                        'justificacion_modificacion' => null,
                        // SIAT: se inicia en PENDIENTE, se completará cuando se pague
                        'estado_siat'              => Factura::SIAT_PENDIENTE,
                    ]);

                    $lectura->update(['estado' => 'facturado']);

                    // Acumular el afiliado_id sin llamar a verificarEstadoFinanciero aquí
                    if ($lectura->conexion?->afiliado_id) {
                        $afiliadosAfectados[] = $lectura->conexion->afiliado_id;
                    }

                    $generadasCount++;
                }
            });

            // OPTIMIZACIÓN: Ahora que la transacción terminó exitosamente,
            // re-evaluamos el estado financiero de cada afiliado afectado (sin duplicados).
            foreach (array_unique($afiliadosAfectados) as $afiliadoId) {
                Afiliado::verificarEstadoFinanciero($afiliadoId);
            }

        } catch (\Exception $e) {
            Log::error('Fallo la transacción de facturación masiva', [
                'error'       => $e->getMessage(),
                'lectura_ids' => $lecturaIds,
            ]);
            return redirect()->back()->withErrors([
                'error_general' => 'Error durante la generación. No se creó ninguna factura. Detalles: ' . $e->getMessage(),
            ]);
        }

        return redirect()->route('facturacion.generar.show')
            ->with('success', "¡Éxito! Se generaron {$generadasCount} facturas nuevas.");
    }
}