<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Este es un modelo Pivot personalizado.
 * Representa la tabla 'afiliado_requisito'.
 */
class AfiliadoRequisito extends Pivot
{
    /**
     * La tabla asociada con el modelo pivot.
     *
     * @var string
     */
    protected $table = 'afiliado_requisito';

    /**
     * Indica si el modelo debe tener timestamps.
     *
     * @var bool
     */
    public $timestamps = true; // Ya que definimos created_at/updated_at

    /**
     * Conversión de tipos (casts) para la fecha.
     */
    protected $casts = [
        'fecha_entrega' => 'date:Y-m-d',
    ];
}