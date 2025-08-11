<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $table = 'facturas';

    protected $fillable = [
        'conexion_id',
        'lectura_id',
        'consumo_m3',
        'monto_total',
        'estado',
        'fecha_emision',
        'fecha_pago',
        
    ];

    // La factura pertenece a una conexión
    public function conexion()
    {
        return $this->belongsTo(Conexion::class, 'conexion_id');
    }

    // La factura pertenece a una lectura
    public function lectura()
    {
        return $this->belongsTo(Lectura::class, 'lectura_id');
    }

    // Una factura tiene muchos pagos
    public function pagos()
    {
        return $this->hasMany(Pago::class, 'factura_id');
    }
}
