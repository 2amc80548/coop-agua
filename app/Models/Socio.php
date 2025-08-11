<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Socio extends Model
{
    protected $table = 'socios';

    protected $fillable = [
        'nombre_completo',
        'ci',
        'telefono',
        'direccion',
    ];

    // Un socio puede tener muchas conexiones
    public function conexiones()
    {
        return $this->hasMany(Conexion::class, 'socio_id');
    }
    
}
