<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// ¡Añadimos estos dos!
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Afiliado extends Model
{
    protected $table = 'afiliados';

    /*
     */
    protected $fillable = [
        'nombre_completo',
        'ci',
        'celular',
        'direccion',
        'zona_id', 
        'tipo',
        'fecha_afiliacion',
        'fecha_baja',
        'codigo',
        'tenencia',
        'estado',
        'estado_servicio', 
        'adulto_mayor',
        'profile_photo_path', 
    ];

    /**
     * Conversión de tipos (casts).
     * ¡Actualizado con los nuevos campos!
     */
    protected $casts = [
        'fecha_afiliacion' => 'date:Y-m-d',
        'fecha_baja'       => 'date:Y-m-d',
        'adulto_mayor'     => 'boolean',
    ];

    // --- NUEVAS RELACIONES PROFESIONALES ---

    /**
     * Define la relación "pertenece a":
     * Un Afiliado pertenece a una Zona.
     */
    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'zona_id');
    }

    /**
     * Define la relación "muchos a muchos":
     * Un Afiliado puede tener muchos Requisitos.
     */
    public function requisitos(): BelongsToMany
    {
        return $this->belongsToMany(Requisito::class, 'afiliado_requisito') // Nombre de la tabla pivot
                    ->using(AfiliadoRequisito::class) // Usa nuestro modelo Pivot personalizado
                    ->withPivot('fecha_entrega', 'observacion') // Carga los campos extra
                    ->withTimestamps(); // Maneja created_at/updated_at
    }


    /**
     * Un Afiliado tiene muchas Conexiones.
     */
    public function conexiones()
    {
        return $this->hasMany(Conexion::class, 'afiliado_id');
    }

    /**
     * Un Afiliado puede tener una cuenta de Usuario.
     */
    public function user()
    {
        return $this->hasOne(User::class, 'afiliado_id');
    }
}