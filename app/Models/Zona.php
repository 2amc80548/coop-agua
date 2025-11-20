<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zona extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'zonas';

    /**
     * Indica si el modelo debe tener timestamps (created_at, updated_at).
     * Lo ponemos en false porque es una tabla simple de catálogo.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
    ];

    /**
     * Define la relación: Una Zona puede tener muchos Afiliados.
     */
    public function afiliados()
    {
        return $this->hasMany(Afiliado::class, 'zona_id');
    }

}