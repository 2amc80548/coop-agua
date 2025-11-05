<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Afiliado;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conexion extends Model
{
    protected $table = 'conexiones';

    protected $fillable = [
        'codigo_medidor',
        'afiliado_id',      
        'estado',
        'direccion',
        'zona_id',
        'fecha_instalacion',
        'tipo_conexion',
    ];

    // La conexión pertenece a un afiliado
    public function afiliado()
    {
        return $this->belongsTo(Afiliado::class, 'afiliado_id');
    }


    public function lecturas()
    {
        return $this->hasMany(Lectura::class, 'conexion_id');
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'conexion_id');
    }

    public function accesosAutorizados()
    {
        return $this->hasMany(AccesoAutorizado::class, 'conexion_id');
    }
    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'zona_id');
    }
}
