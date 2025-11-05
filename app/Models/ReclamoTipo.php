<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReclamoTipo extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     */
    protected $table = 'reclamo_tipos';

    /**
     * Indica si el modelo debe tener timestamps (created_at, updated_at).
     * Lo ponemos en 'false' porque esta tabla es solo un catálogo.
     */
    public $timestamps = false;

    /**
     * Los atributos que se pueden asignar masivamente (solo 'nombre').
     */
    protected $fillable = [
        'nombre',
    ];

    /**
     * Define la relación: Un Tipo de Reclamo puede estar en muchos Reclamos.
     */
    public function reclamos()
    {
        // Esto nos permitirá en el futuro contar cuántos reclamos hay de cada tipo.
        return $this->hasMany(Reclamo::class, 'reclamo_tipo_id');
    }
}