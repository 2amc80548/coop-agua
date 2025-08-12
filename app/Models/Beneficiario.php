<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Beneficiario extends Model
{
    protected $table = 'beneficiarios';

    protected $fillable = [
        'nombre_completo',
        'ci',
        'telefono',
        'direccion',
        'tipo',
    ];

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function conexiones()
    {
        return $this->hasMany(Conexion::class, 'beneficiario_id');
    }
}
