<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Factura
 *
 * Maneja tanto el ciclo de vida interno del sistema (impaga/pagado/anulada)
 * como el ciclo de vida fiscal ante el SIN Bolivia (PENDIENTE/VALIDADA/RECHAZADA).
 *
 * Los campos del Bloque SIAT son nullable para garantizar compatibilidad
 * con facturas históricas generadas antes de la integración con Impuestos Nacionales.
 */
class Factura extends Model
{
    protected $table = 'facturas';

    // ---------------------------------------------------------------
    // Constantes: Estado interno del sistema
    // ---------------------------------------------------------------
    const ESTADO_IMPAGA  = 'impaga';
    const ESTADO_PAGADO  = 'pagado';
    const ESTADO_ANULADA = 'anulada';

    // ---------------------------------------------------------------
    // Constantes: Estado SIAT (ciclo de vida fiscal ante el SIN)
    // ---------------------------------------------------------------
    const SIAT_PENDIENTE    = 'PENDIENTE';
    const SIAT_EN_PROCESO   = 'EN_PROCESO';
    const SIAT_VALIDADA     = 'VALIDADA';
    const SIAT_RECHAZADA    = 'RECHAZADA';
    const SIAT_ANULADA      = 'ANULADA_SIAT';
    const SIAT_OBSERVADA    = 'OBSERVADA';
    const SIAT_CONTINGENCIA = 'CONTINGENCIA';

    // ---------------------------------------------------------------
    // Catálogo de Tipos de Documento de Identidad (SIN Bolivia)
    // ---------------------------------------------------------------
    const TIPO_DOC_CI        = 1; // Cédula de Identidad
    const TIPO_DOC_CEX       = 2; // Cédula de Extranjería
    const TIPO_DOC_PASAPORTE = 3; // Pasaporte
    const TIPO_DOC_OTRO      = 4; // Otro Documento
    const TIPO_DOC_NIT       = 5; // NIT (Empresas)

    protected $fillable = [
        // --- Datos internos del sistema ---
        'conexion_id',
        'lectura_id',
        'periodo',
        'consumo_m3',
        'monto_total',
        'deuda_pendiente',
        'estado',
        'fecha_emision',
        'fecha_vencimiento',
        'fecha_pago',
        'justificacion_modificacion',
        'motivo_anulacion',
        'anulado_por',
        'fecha_anulacion',

        // --- Bloque SIAT: Identificadores Fiscales ---
        'cuf',
        'cufd',
        'numero_factura',

        // --- Bloque SIAT: Datos del Cliente ---
        'razon_social',
        'numero_documento',
        'complemento_documento',
        'codigo_tipo_documento_identidad',

        // --- Bloque SIAT: Estado y Sincronización SIN ---
        'estado_siat',
        'codigo_recepcion_siat',
        'leyenda_siat',
        'xml_path',

        // --- Bloque SIAT: Anulación ---
        'motivo_anulacion_siat_id',
    ];

    /**
     * Conversión de tipos (casts) para que Laravel maneje bien los datos.
     */
    protected $casts = [
        // Fechas del sistema
        'fecha_emision'    => 'date:Y-m-d',
        'fecha_vencimiento' => 'date:Y-m-d',
        'fecha_pago'       => 'date:Y-m-d',

        // Montos monetarios
        'monto_total'      => 'decimal:2',
        'deuda_pendiente'  => 'decimal:2',
        'consumo_m3'       => 'decimal:2',

        // Campos SIAT de tipo entero
        'numero_factura'                   => 'integer',
        'codigo_tipo_documento_identidad'  => 'integer',
        'motivo_anulacion_siat_id'         => 'integer',
    ];

    // ---------------------------------------------------------------
    // Relaciones Eloquent
    // ---------------------------------------------------------------

    /**
     * La factura pertenece a una conexión.
     */
    public function conexion()
    {
        return $this->belongsTo(Conexion::class, 'conexion_id');
    }

    /**
     * La factura pertenece a una lectura.
     */
    public function lectura()
    {
        return $this->belongsTo(Lectura::class, 'lectura_id');
    }

    /**
     * Una factura tiene un historial de pagos.
     */
    public function pagos()
    {
        return $this->hasMany(Pago::class, 'factura_id');
    }

    public function usuarioAnulacion()
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }

    // ---------------------------------------------------------------
    // Helpers / Accessors
    // ---------------------------------------------------------------

    /**
     * Indica si la factura ya fue enviada y validada exitosamente por el SIN.
     */
    public function estaValidadaPorSiat(): bool
    {
        return $this->estado_siat === self::SIAT_VALIDADA;
    }

    /**
     * Indica si la factura está pendiente de envío al SIN.
     */
    public function pendienteDeEnvioSiat(): bool
    {
        return $this->estado_siat === self::SIAT_PENDIENTE
            && $this->estado === self::ESTADO_PAGADO;
    }
}