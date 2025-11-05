<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tarifa extends Model
{
    protected $table = 'tarifas';

    protected $fillable = [
        'vigente_desde',
        'vigente_hasta',
        'activo',
        'min_m3',
        'min_monto',
        'precio_m3',
        'descuento_adulto_mayor_pct',
        'afiliacion_socio_monto',
        'afiliacion_usuario_monto',
        'multa_corte_monto',
        'cisterna_10k_monto',
        'notas',
    ];

    protected $casts = [
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
        'activo' => 'boolean',
        'min_m3' => 'integer',
        'min_monto' => 'decimal:2',
        'precio_m3' => 'decimal:2',
        'descuento_adulto_mayor_pct' => 'decimal:2',
        'afiliacion_socio_monto' => 'decimal:2',
        'afiliacion_usuario_monto' => 'decimal:2',
        'multa_corte_monto' => 'decimal:2',
        'cisterna_10k_monto' => 'decimal:2',
    ];

    public function conceptos()
    {
        return $this->hasMany(TarifaConcepto::class, 'tarifa_id');
    }

    public static function vigente(?string $fecha = null): ?self
    {
        $fecha = $fecha ?: now()->toDateString();

        $tarifa = self::where(function ($q) use ($fecha) {
                $q->where('vigente_desde', '<=', $fecha)
                  ->where(function ($q2) use ($fecha) {
                      $q2->whereNull('vigente_hasta')
                         ->orWhere('vigente_hasta', '>=', $fecha);
                  });
            })
            ->orderByDesc('vigente_desde')
            ->first();

        if (!$tarifa) {
            $tarifa = self::where('activo', 1)->orderByDesc('vigente_desde')->first();
        }

        return $tarifa;
    }
}
