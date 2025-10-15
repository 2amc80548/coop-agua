<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AfiliadoRequisito extends Model
{
    protected $table = 'afiliado_requisitos';

    protected $fillable = [
        'afiliado_id',
        'escenario',            // copia de tenencia
        'fotocopia_ci',
        'plano_ubicacion',
        'doc_compra_venta',
        'croquis',
        'certificacion_otb',
        'observaciones',
        'registrado_por',
    ];

    protected $casts = [
        'fotocopia_ci'      => 'boolean',
        'plano_ubicacion'   => 'boolean',
        'doc_compra_venta'  => 'boolean',
        'croquis'           => 'boolean',
        'certificacion_otb' => 'boolean',
    ];

    public function afiliado()
    {
        return $this->belongsTo(Afiliado::class);
    }
}
