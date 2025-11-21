<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Conexion; 
use App\Models\Factura; 
use App\Models\User;

class Lectura extends Model
{
    protected $table = 'lecturas';

    protected $fillable = [
        'conexion_id',
        'fecha_lectura',
        'periodo', 
        'lectura_anterior',
        'lectura_actual',
        'observacion',
        'registrado_por',
        'estado',
    ];

    // Ayuda a Laravel a tratar estos campos correctamente
    protected $casts = [
        'fecha_lectura' => 'date:Y-m-d', 
        'lectura_anterior' => 'decimal:2', 
        'lectura_actual' => 'decimal:2',
    ];
   


 
    public function conexion()
    {
        return $this->belongsTo(Conexion::class, 'conexion_id');
    }

    public function usuarioRegistrado()
    {
         return $this->belongsTo(User::class, 'registrado_por');
    }

    public function factura()
    {
        return $this->hasOne(Factura::class, 'lectura_id');
    }
   
}