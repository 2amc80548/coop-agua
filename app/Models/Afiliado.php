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
        'zona',              
        'tipo',              
        'fecha_afiliacion',
        'fecha_baja',
        'codigo',
        'tenencia',          
        'estado',            
        'adulto_mayor',      
    ];

    protected $casts = [
        'fecha_afiliacion' => 'date',
        'fecha_baja'       => 'date',
        'adulto_mayor'     => 'boolean',
    ];

    public function conexiones()
    {
        return $this->hasMany(Conexion::class, 'afiliado_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'afiliado_id');
    }

    public function requisitos()
    {
        return $this->hasOne(\App\Models\AfiliadoRequisito::class, 'afiliado_id');
    }
}
