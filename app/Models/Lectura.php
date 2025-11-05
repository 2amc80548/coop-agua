<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Conexion; // Asegúrate que esté Conexion
use App\Models\Factura; // Asegúrate que esté Factura
use App\Models\User; // Asegúrate que esté User

class Lectura extends Model
{
    protected $table = 'lecturas';

    protected $fillable = [
        'conexion_id',
        'fecha_lectura',
        'periodo', // <-- ¡¡ASEGÚRATE QUE ESTA LÍNEA EXISTA!!
        'lectura_anterior',
        'lectura_actual',
        'observacion',
        'registrado_por',
        'estado', // <-- ¡AÑADE 'estado' TAMBIÉN! Lo usamos en store()
    ];

    // --- CASTS (Mejora) ---
    // Ayuda a Laravel a tratar estos campos correctamente
    protected $casts = [
        'fecha_lectura' => 'date:Y-m-d', // Formato al leer/escribir
        'lectura_anterior' => 'decimal:2', // Tratar como decimal con 2 precisiones
        'lectura_actual' => 'decimal:2',
    ];
    // --- FIN CASTS ---


    // --- RELACIONES (Tu código aquí está bien) ---
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
    // --- FIN RELACIONES ---
}