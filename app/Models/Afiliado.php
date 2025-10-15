<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Afiliado extends Model
{
    protected $table = 'afiliados';

    protected $fillable = [
        'nombre_completo',
        'ci',
        'celular',            
        'direccion',
        'tipo',               
        'fecha_afiliacion',
        'fecha_baja',
        'codigo',             
        'barrio',
        'tenencia',           // propietario | compra_venta | posesion
        'estado',             // activo | inactivo
    ];

    protected $casts = [
        'fecha_afiliacion' => 'date',
        'fecha_baja'       => 'date',
    ];

    // Relación con conexiones
    public function conexiones()
    {
        return $this->hasMany(Conexion::class, 'afiliado_id');
    }

    // Relación con usuarios (0..2)
    public function users()
    {
        return $this->hasMany(User::class, 'afiliado_id');
    }

   public function requisitos()
    {
        return $this->hasOne(\App\Models\AfiliadoRequisito::class, 'afiliado_id');
    }
}
