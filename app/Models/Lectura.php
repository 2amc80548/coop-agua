<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;


class Lectura extends Model
{
    protected $table = 'lecturas';

    protected $fillable = [
        'conexion_id',
        'fecha_lectura',
        'lectura_anterior',
        'lectura_actual',
        'observacion',
        'registrado_por',
    ];

    // La lectura pertenece a una conexión
    public function conexion()
    {
        return $this->belongsTo(Conexion::class, 'conexion_id');
    }

    // La lectura fue registrada por un usuario (técnico)
    public function usuarioRegistrado()
    {
         return $this->belongsTo(User::class, 'registrado_por');
    }

    // Una lectura tiene una factura asociada
    public function factura()
    {
        return $this->hasOne(Factura::class, 'lectura_id');
    }
}
