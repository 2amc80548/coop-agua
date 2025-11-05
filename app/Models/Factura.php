<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $table = 'facturas';

    /**
     * Campos que se pueden llenar masivamente (fillable).
     * Ajustados a nuestro nuevo flujo profesional.
     */
    protected $fillable = [
        'conexion_id',
        'lectura_id',
        'periodo', // ¡Nuevo!
        'consumo_m3',
        'monto_total',
        'deuda_pendiente', // ¡Nuevo y recomendado!
        'estado', // 'impaga', 'pagado', 'anulada'
        'fecha_emision',
        'fecha_vencimiento', // ¡Nuevo!
        'fecha_pago',
        'justificacion_modificacion', // ¡Nuevo!
    ];

    /**
     * Conversión de tipos (casts) para que Laravel maneje bien los datos.
     * ¡Muy importante para montos decimales y fechas!
     */
    protected $casts = [
        'fecha_emision' => 'date:Y-m-d',
        'fecha_vencimiento' => 'date:Y-m-d',
        'fecha_pago' => 'date:Y-m-d',
        'monto_total' => 'decimal:2',
        'deuda_pendiente' => 'decimal:2',
        'consumo_m3' => 'decimal:2',
    ];

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
     * (Incluso si solo hay 1 pago, es bueno tenerlo como 'hasMany' por historial)
     */
    public function pagos()
    {
        
        return $this->hasMany(Pago::class, 'factura_id');
    }
}