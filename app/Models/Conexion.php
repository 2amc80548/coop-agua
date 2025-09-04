<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Beneficiario;


class Conexion extends Model
{
    protected $table = 'conexiones';

    protected $fillable = [
        'codigo_medidor',
        'beneficiario_id',
        'estado',
        'direccion',
        'zona',
        'fecha_instalacion',
        'tipo_conexion',
    ];

    // La conexión pertenece a un beneficiario
    public function beneficiario()
    {
        return $this->belongsTo(Beneficiario::class, 'beneficiario_id');
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
