<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TarifaConcepto extends Model
{
    protected $table = 'tarifa_conceptos';

    protected $fillable = [
        'tarifa_id',
        'codigo',
        'nombre',
        'tipo',          // FIJO | PORCENTAJE
        'valor',
        'aplica_sobre',  
        'activo',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function tarifa()
    {
        return $this->belongsTo(Tarifa::class, 'tarifa_id');
    }
}
