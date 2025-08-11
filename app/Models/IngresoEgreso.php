<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngresoEgreso extends Model
{
    protected $table = 'ingresos_egresos';

    protected $fillable = [
        'tipo',
        'categoria',
        'descripcion',
        'monto',
        'fecha',
        'registrado_por',
    ];

    // Fue registrado por un usuario
    public function usuarioRegistrado()
    {
        return $this->belongsTo(Usuario::class, 'registrado_por');
    }
}
