<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccesoAutorizado extends Model
{
    protected $table = 'accesos_autorizados';

    protected $fillable = [
        'conexion_id',
        'ci_autorizado',
        'nombre',
        'fecha_registro',
        'activo',
    ];

    // Pertenece a una conexión
    public function conexion()
    {
        return $this->belongsTo(Conexion::class, 'conexion_id');
    }
}
