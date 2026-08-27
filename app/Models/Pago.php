<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';

    // ---------------------------------------------------------------
    // Constantes: Formas de Pago aceptadas
    // ---------------------------------------------------------------
    const FORMA_EFECTIVO = 'Efectivo';
    const FORMA_QR       = 'QR';

    protected $fillable = [
        'factura_id',
        'monto_pagado',
        'forma_pago',
        'fecha_pago',
        'referencia',
        'registrado_por',
    ];

    /**
     * Conversión de tipos para que Laravel maneje correctamente los datos.
     * Sin estos casts, fecha_pago llega como string al frontend Vue y
     * monto_pagado puede retornar como string en lugar de float.
     */
    protected $casts = [
        'fecha_pago'   => 'date:Y-m-d',
        'monto_pagado' => 'decimal:2',
    ];

    // ---------------------------------------------------------------
    // Relaciones Eloquent
    // ---------------------------------------------------------------

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }

    public function usuarioRegistrado()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
