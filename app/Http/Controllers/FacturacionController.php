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
        // 1. Obtener la tarifa activa. Si no hay, no podemos facturar.
        $tarifaActiva = $this->getTarifaActiva();
        if (!$tarifaActiva) {
            // Redirigir al dashboard con un error grave
            return redirect()->route('admin.dashboard') // O a donde prefieras
                ->withErrors(['error_general' => '¡Error Crítico! No hay ninguna tarifa activa. Configure una antes de facturar.']);
        }

        // 2. Determinar el período a mostrar
        // Si el usuario filtra por un período, usar ese. Si no, usar el último período con lecturas pendientes.
        $filtroPeriodo = $request->input('periodo');
        
        $queryPeriodos = Lectura::where('estado', 'pendiente')
                                ->select(DB::raw('DISTINCT periodo'))
                                ->orderBy('periodo', 'desc');

        if (!$filtroPeriodo) {
            $filtroPeriodo = $queryPeriodos->first()?->periodo; // Tomar el más reciente pendiente
        }
        
        // Obtener todos los períodos que tienen lecturas pendientes para el dropdown
        $periodosDisponibles = $queryPeriodos->pluck('periodo');

        // 3. Obtener las lecturas pendientes para ese período
        $lecturasPendientes = [];
        if ($filtroPeriodo) {
            $lecturasPendientes = Lectura::with([
                'conexion:id,codigo_medidor,afiliado_id,direccion',
                'conexion.afiliado:id,nombre_completo,ci,adulto_mayor' // Cargar afiliado y estado de adulto_mayor
            ])
            ->where('estado', 'pendiente')
            ->where('periodo', $filtroPeriodo)
            ->get()
            ->map(function ($lectura) use ($tarifaActiva) {
                // 4. Calcular el monto estimado para cada lectura y añadirlo
                $calculo = $this->calcularMontoFactura($lectura, $tarifaActiva);
                $lectura->consumo_calculado = $calculo['consumo'];
                $lectura->monto_estimado = $calculo['total'];
                $lectura->descuento_aplicado = $calculo['descuento'];
                return $lectura;
            });
        }

        // 5. Enviar los datos a la vista Vue
        return Inertia::render('Facturacion/Generar', [
            'lecturasPendientes' => $lecturasPendientes,
            'periodosDisponibles' => $periodosDisponibles,
            'filters' => ['periodo' => $filtroPeriodo],
            'tarifaInfo' => [ // Enviar info de la tarifa por si Vue la necesita
                'precio_m3' => $tarifaActiva->precio_m3,
                'min_monto' => $tarifaActiva->min_monto,
            ],
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

        // 2. Obtener la tarifa activa (fallar si no existe)
        $tarifaActiva = $this->getTarifaActiva();
        if (!$tarifaActiva) {
            return redirect()->back()->withErrors(['error_general' => '¡Error Crítico! No hay tarifa activa. No se generó ninguna factura.']);
        }

        $lecturaIds = $validated['lectura_ids'];
        $generadasCount = 0;
        $erroresCount = 0;

        // 3. Usar una transacción de Base de Datos
        //    Si una factura falla, NINGUNA se crea y NINGUNA lectura se actualiza.
        try {
            DB::transaction(function () use ($lecturaIds, $tarifaActiva, &$generadasCount, &$erroresCount) {
                
                // 4. Obtener todas las lecturas seleccionadas que AÚN estén pendientes
                $lecturasParaProcesar = Lectura::with('conexion.afiliado') // Cargar afiliado para descuento
                    ->whereIn('id', $lecturaIds)
                    ->where('estado', 'pendiente') // ¡Muy importante! Evita facturar doble
                    ->get();

                foreach ($lecturasParaProcesar as $lectura) {
                    try {
                        // 5. Calcular el monto final
                        $calculo = $this->calcularMontoFactura($lectura, $tarifaActiva);
                        
                        // 6. Calcular fecha de vencimiento (ej. 30 días después de la fecha de emisión)
                        $fechaEmision = Carbon::now();
                        $fechaVencimiento = $fechaEmision->copy()->addDays(30); // Ajusta tus días de vencimiento

                        // 7. Crear la Factura
                        Factura::create([
                            'conexion_id' => $lectura->conexion_id,
                            'lectura_id' => $lectura->id,
                            'periodo' => $lectura->periodo,
                            'fecha_emision' => $fechaEmision,
                            'fecha_vencimiento' => $fechaVencimiento,
                            'consumo_m3' => $calculo['consumo'],
                            'monto_total' => $calculo['total'], // Monto final sin redondeo
                            'deuda_pendiente' => $calculo['total'], // Deuda inicial es el total
                            'estado' => 'impaga', // Estado inicial 'impaga'
                            'justificacion_modificacion' => null, // Sin modificación manual
                        ]);

                        // 8. Actualizar la Lectura
                        $lectura->estado = 'facturado';
                        $lectura->save();

                        $generadasCount++;
                    } catch (\Exception $e) {
                        // Registrar error de una lectura individual pero continuar con las demás
                        Log::error("Error al facturar lectura ID: {$lectura->id}", ['error' => $e->getMessage()]);
                        $erroresCount++;
                        // Como estamos en una transacción, si una falla, todas fallarán.
                        // Para permitir fallos parciales, habría que quitar la transacción
                        // o manejarlo de forma más compleja.
                        // Por seguridad, mantenemos la transacción: si una falla, todo se revierte.
                        
                        // Lanzar el error de nuevo para forzar el Rollback de la transacción
                        throw new \Exception("Error procesando lectura {$lectura->id}: " . $e->getMessage());
                    }
                }
            }); // Fin de la transacción

        } catch (\Exception $e) {
            // Capturar el error que forzó el Rollback
            Log::error('Fallo la transacción de facturación masiva', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['error_general' => 'Error durante la generación. No se creó ninguna factura. Detalles: ' . $e->getMessage()]);
        }

        // 9. Redirigir con mensaje de éxito
        $mensaje = "¡Éxito! Se generaron {$generadasCount} facturas nuevas.";
        if ($erroresCount > 0) {
             $mensaje = "Proceso completado con {$erroresCount} errores. Ninguna factura fue creada debido a un error en la transacción.";
        }

        return redirect()->route('facturacion.generar.show') // Redirigir a la misma página
                       ->with('success', $mensaje);
    }

    /**
     * Obtiene la tarifa activa de la base de datos.
     * @return Tarifa|null
     */
    private function getTarifaActiva()
    {
        return Tarifa::where('activo', 1)->orderBy('vigente_desde', 'desc')->first();
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
            'total' => ($totalFinal >= 0) ? $totalFinal : 0, // Asegurar que no sea negativo
        ];
    }

} // Fin de la clase