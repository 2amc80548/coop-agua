<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lectura;
use App\Models\Factura;
use App\Models\Tarifa;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FacturacionController extends Controller
{
    /**
     * Muestra la página para generar facturas.
     * Carga las lecturas pendientes de un período específico.
     */
    public function showGenerador(Request $request)
    {
        // 1. Determinar el período a mostrar (Se eliminó la verificación de tarifa global)
        $filtroPeriodo = $request->input('periodo');
        
        $queryPeriodos = Lectura::where('estado', 'pendiente')
            ->select(DB::raw('DISTINCT periodo'))
            ->orderBy('periodo', 'desc');

        if (!$filtroPeriodo) {
            $filtroPeriodo = $queryPeriodos->first()?->periodo; // Tomar el más reciente pendiente
        }
        
        // Obtener todos los períodos que tienen lecturas pendientes para el dropdown
        $periodosDisponibles = $queryPeriodos->pluck('periodo');

        // 2. Obtener las lecturas pendientes para ese período
        $lecturasPendientes = [];
        if ($filtroPeriodo) {
            $lecturasPendientes = Lectura::with([
                'conexion:id,codigo_medidor,afiliado_id,direccion,tipo_conexion', // Se agrega tipo_conexion para la tarifa
                'conexion.afiliado:id,nombre_completo,ci,adulto_mayor' // Cargar afiliado y estado de adulto_mayor
            ])
            ->where('estado', 'pendiente')
            ->where('periodo', $filtroPeriodo)
            ->get()
            ->map(function ($lectura) {
                
                // 3. OBTENER LA TARIFA ESPECÍFICA (CORRECCIÓN CLAVE)
                $tarifa = $this->getTarifaForLectura($lectura);

                // Manejo de error si no se encuentra la tarifa
                if (!$tarifa) {
                    $lectura->consumo_calculado = $lectura->lectura_actual - $lectura->lectura_anterior;
                    $lectura->monto_estimado = 0;
                    $lectura->descuento_aplicado = 0;
                    $lectura->error_tarifa = 'No hay tarifa activa para el tipo: ' . ($lectura->conexion->tipo_conexion ?? 'DESCONOCIDO');
                    return $lectura;
                }

                // 4. Calcular el monto estimado para cada lectura y añadirlo (usando $tarifa)
                $calculo = $this->calcularMontoFactura($lectura, $tarifa);
                $lectura->consumo_calculado = $calculo['consumo'];
                $lectura->monto_estimado = $calculo['total'];
                $lectura->descuento_aplicado = $calculo['descuento'];
                $lectura->error_tarifa = null;
                return $lectura;
            });
        }

        // 5. Enviar los datos a la vista Vue (Se eliminó la info de tarifa global)
        return Inertia::render('Facturacion/Generar', [
            'lecturasPendientes' => $lecturasPendientes,
            'periodosDisponibles' => $periodosDisponibles,
            'filters' => ['periodo' => $filtroPeriodo],
        ]);
    }

    /**
     * Procesa las lecturas seleccionadas y genera las facturas.
     */
    public function storeGeneracion(Request $request)
    {
        // 1. Validar que recibimos los IDs de las lecturas
        $validated = $request->validate([
            'lectura_ids' => 'required|array|min:1',
            'lectura_ids.*' => 'integer|exists:lecturas,id', // Asegurar que cada ID exista
        ]);

        // 2. [SE ELIMINÓ la llamada a getTarifaForLectura($lectura) y la verificación fallida de $tarifaActiva]

        $lecturaIds = $validated['lectura_ids'];
        $generadasCount = 0;
        $erroresCount = 0;
        $erroresDetalle = []; // Usamos este array para mejor log

        // 3. Usar una transacción de Base de Datos
        try {
            // Se elimina $tarifaActiva de la cláusula 'use' ya que es variable por lectura
            DB::transaction(function () use ($lecturaIds, &$generadasCount, &$erroresCount, &$erroresDetalle) {
                
                // 4. Obtener todas las lecturas seleccionadas que AÚN estén pendientes
                $lecturasParaProcesar = Lectura::with('conexion.afiliado') 
                    ->whereIn('id', $lecturaIds)
                    ->where('estado', 'pendiente') 
                    ->get();

                foreach ($lecturasParaProcesar as $lectura) {
                    try {
                        // 5. OBTENER LA TARIFA ESPECÍFICA (CORRECCIÓN CLAVE)
                        $tarifa = $this->getTarifaForLectura($lectura);

                        if (!$tarifa) {
                             // Lanza una excepción si no hay tarifa para forzar el rollback
                             throw new \Exception("No se encontró tarifa activa para el tipo de conexión: " . ($lectura->conexion->tipo_conexion ?? 'DESCONOCIDO'));
                        }

                        // 6. Calcular el monto final (usando $tarifa, que está correctamente definida)
                        $calculo = $this->calcularMontoFactura($lectura, $tarifa);
                        
                        // 7. Calcular fecha de vencimiento (ej. 30 días después de la fecha de emisión)
                        $fechaEmision = Carbon::now();
                        $fechaVencimiento = $fechaEmision->copy()->addDays(30); 

                        // 8. Crear la Factura
                        Factura::create([
                            'conexion_id' => $lectura->conexion_id,
                            'lectura_id' => $lectura->id,
                            'periodo' => $lectura->periodo,
                            'fecha_emision' => $fechaEmision,
                            'fecha_vencimiento' => $fechaVencimiento,
                            'consumo_m3' => $calculo['consumo'],
                            'monto_total' => $calculo['total'], 
                            'deuda_pendiente' => $calculo['total'], 
                            'estado' => 'impaga', 
                            'justificacion_modificacion' => null, 
                        ]);

                        // 9. Actualizar la Lectura
                        $lectura->estado = 'facturado';
                        $lectura->save();

                        $generadasCount++;
                    } catch (\Exception $e) {
                        Log::error("Error al facturar lectura ID: {$lectura->id}", ['error' => $e->getMessage()]);
                        $erroresCount++;
                        // Lanzar el error para forzar el Rollback de TODA la transacción
                        throw $e; 
                    }
                }
            }); // Fin de la transacción

        } catch (\Exception $e) {
            // Capturar el error que forzó el Rollback
            $mensajeError = count($erroresDetalle) > 0 ? $erroresDetalle[0] : $e->getMessage();
            Log::error('Fallo la transacción de facturación masiva', ['error' => $mensajeError]);
            return redirect()->back()->withErrors(['error_general' => 'Error durante la generación. No se creó ninguna factura. Detalles: ' . $mensajeError]);
        }

        // 10. Redirigir con mensaje de éxito
        $mensaje = "¡Éxito! Se generaron {$generadasCount} facturas nuevas.";
        if ($erroresCount > 0) {
            // Este caso solo se daría si la transacción no logra revertir correctamente el mensaje, pero
            // por la naturaleza de la transacción, si hay un error, el count debe ser 0.
             $mensaje = "Proceso fallido. Revise los logs para ver el error que causó la reversión de la transacción.";
        }

        return redirect()->route('facturacion.generar.show') 
            ->with('success', $mensaje);
    }

    /**
     * Obtiene la tarifa activa de la base de datos de forma genérica.
     * @return Tarifa|null
     */
    private function getTarifaActiva(string $tipoConexion = null)
    {
        $query = Tarifa::where('activo', 1);

        if ($tipoConexion) {
            $query->where('tipo_conexion', $tipoConexion);
        }
        
        return $query->orderBy('vigente_desde', 'desc')->first();
    }


    /**
     * Obtiene la tarifa activa de la base de datos específica para una conexión.
     * ESTA ES LA FUNCIÓN CORRECTA PARA FACTURAR POR TIPO DE CONEXIÓN.
     * @param Lectura $lectura
     * @return Tarifa|null
     */
    private function getTarifaForLectura(Lectura $lectura)
    {
        $tipo = $lectura->conexion->tipo_conexion;
        
        return Tarifa::where('activo', 1)
            ->where('tipo_conexion', $tipo) // Filtra por el tipo de conexión.
            ->orderBy('vigente_desde', 'desc')
            ->first();
    }

    /**
     * Lógica centralizada para calcular el monto de una factura.
     * @param Lectura $lectura
     * @param Tarifa $tarifa
     * @return array [consumo, monto_base, descuento, total]
     */
    private function calcularMontoFactura(Lectura $lectura, Tarifa $tarifa)
    {
        $consumo = $lectura->lectura_actual - $lectura->lectura_anterior;
        $montoCalculado = 0;

        // --- Lógica de Cálculo de Tarifa ---
        // REGLA: Si el consumo es menor al mínimo Y el cobro mínimo es mayor
        // que el consumo por el precio, se cobra el mínimo.
        $calculoDirecto = $consumo * $tarifa->precio_m3;
        
        if ($consumo <= $tarifa->min_m3 && $tarifa->min_monto > $calculoDirecto) {
            $montoCalculado = $tarifa->min_monto;
        } else {
            // Si no, se cobra el consumo por el precio
            $montoCalculado = $calculoDirecto;
        }
        // --- Fin Lógica Cálculo ---

        // --- Lógica de Descuento ---
        $descuento = 0;
        // Verificar si la relación afiliado está cargada y si es adulto mayor
        if ($lectura->conexion->afiliado?->adulto_mayor && $tarifa->descuento_adulto_mayor_pct > 0) {
            // Asumimos descuento sobre el monto calculado (ajusta si tu regla es otra)
            $descuento = ($montoCalculado * $tarifa->descuento_adulto_mayor_pct) / 100;
        }
        
        $totalFinal = $montoCalculado - $descuento;

        return [
            'consumo' => $consumo,
            'monto_base' => $montoCalculado,
            'descuento' => $descuento,
            'total' => round(($totalFinal >= 0) ? $totalFinal : 0, 2), // Asegurar no negativo y redondear
        ];
    }

} // Fin de la clase