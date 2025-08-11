<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conexion extends Model
{
    protected $table = 'conexiones';

    protected $fillable = [
        'codigo_medidor',
        'socio_id',
        'estado',
        'direccion',
        'zona',
    ];

    // La conexión pertenece a un socio
    public function socio()
    {
        return $this->belongsTo(Socio::class, 'socio_id');
    }

    // Una conexión tiene muchas lecturas
    public function lecturas()
    {
        return $this->hasMany(Lectura::class, 'conexion_id');
    }

    // Una conexión tiene muchas facturas
    public function facturas()
    {
        return $this->hasMany(Factura::class, 'conexion_id');
    }

    // Una conexión puede tener varios accesos autorizados
    public function accesosAutorizados()
    {
        return $this->hasMany(AccesoAutorizado::class, 'conexion_id');
    }
}
