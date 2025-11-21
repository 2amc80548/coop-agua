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
        'forma_pago',
        'fecha_pago',
        'referencia',
        'registrado_por',
        
    ];

 
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
