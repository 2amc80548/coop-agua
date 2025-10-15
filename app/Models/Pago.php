<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'factura_id',
        'monto_pagado',
        'metodo_pago',
        'fecha_pago',
        'registrado_por',
    ];

    // El pago pertenece a una factura
    public function factura()
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }

    // El pago fue registrado por un usuario
    public function usuarioRegistrado()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
