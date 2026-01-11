<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Afiliado extends Model
{
    protected $table = 'afiliados';

    /*
     */
    protected $fillable = [
        'nombre_completo',
        'ci',
        'celular',
        'direccion',
        'zona_id', 
        'tipo',
        'fecha_afiliacion',
        'fecha_baja',
        'codigo',
        'tenencia',
        'estado',
        'estado_servicio', 
        'adulto_mayor',
        'profile_photo_path', 
        'observacion'
    ];

    /**
     * Conversión de tipos (casts).
     */
    protected $casts = [
        'fecha_afiliacion' => 'date:Y-m-d',
        'fecha_baja'       => 'date:Y-m-d',
        'adulto_mayor'     => 'boolean',
    ];


    /**
     * Define la relación "pertenece a":
     */
    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'zona_id');
    }

    /**
     * Un Afiliado puede tener muchos Requisitos.
     */
    public function requisitos(): BelongsToMany
    {
        return $this->belongsToMany(Requisito::class, 'afiliado_requisito')
                    ->using(AfiliadoRequisito::class) 
                    ->withPivot('fecha_entrega', 'observacion') 
                    ->withTimestamps(); 
    }


    /**
     * Un Afiliado tiene muchas Conexiones.
     */
    public function conexiones()
    {
        return $this->hasMany(Conexion::class, 'afiliado_id');
    }

    /**
     * Un Afiliado puede tener una cuenta de Usuario.
     */
    public function user()
    {
        return $this->hasOne(User::class, 'afiliado_id');
    }

    /**
     * EL CEREBRO: Revisa las deudas y actualiza el estado automáticamente.
     * Se llama al Pagar y al Facturar.
     */
public static function verificarEstadoFinanciero($afiliadoId)
    {
        $afiliado = self::find($afiliadoId);
        
        // Validación inicial
        if (!$afiliado) return;

        // Limpiamos el estado actual de espacios y mayúsculas para evitar errores
        $estadoActual = strtolower(trim($afiliado->estado_servicio));

        // Si es "pendiente", no hacemos nada.
        if ($estadoActual === 'pendiente') {
            return;
        }

        // 1. Contamos deudas impagas
        $totalDeudas = \App\Models\Factura::whereHas('conexion', function($q) use ($afiliadoId){
                            $q->where('afiliado_id', $afiliadoId);
                        })
                        ->where('estado', 'impaga')
                        ->count();

        // CASO A: REHABILITACIÓN (Debe 0, 1 o 2 facturas)
        if ($totalDeudas < 3) {
            // Ló gica: Si debe menos de 3, NO debería estar castigado.
            // Si su estado NO es 'activo' (ej: está 'en_corte', 'cortado', 'suspendido'), lo activamos.
            if ($estadoActual !== 'activo') {
                $afiliado->estado_servicio = 'activo';
                $afiliado->save();
            }
        }

        // CASO B: CASTIGO (Debe 3 o más)
        if ($totalDeudas >= 3) {
            // Solo lo pasamos a 'en_corte' si estaba confiado en 'activo'.
            // Si ya estaba 'cortado' o 'suspendido', NO lo tocamos.
            if ($estadoActual === 'activo') {
                $afiliado->estado_servicio = 'en_corte';
                $afiliado->save();
            }
        }
    }
}