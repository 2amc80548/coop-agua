<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Requisito extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'requisitos';

    /**
     * Sin timestamps para esta tabla de catálogo.
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
        'descripcion',
    ];

    /**
     * Define la relación "muchos a muchos":
     * Un Requisito puede ser tenido por muchos Afiliados.
     */
    public function afiliados()
    {
        return $this->belongsToMany(Afiliado::class, 'afiliado_requisito')
                    ->withPivot('fecha_entrega', 'observacion') 
                    ->withTimestamps(); 
    }
}