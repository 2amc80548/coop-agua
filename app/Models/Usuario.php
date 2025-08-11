<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'nombre_completo',
        'ci',
        'telefono',
        'email',
        'password',
        'rol',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Un usuario puede registrar muchas lecturas
    public function lecturasRegistradas()
    {
        return $this->hasMany(Lectura::class, 'registrado_por');
    }

    // Un usuario puede registrar muchos pagos
    public function pagosRegistrados()
    {
        return $this->hasMany(Pago::class, 'registrado_por');
    }

    // Un usuario puede registrar muchos ingresos o egresos
    public function ingresosEgresosRegistrados()
    {
        return $this->hasMany(IngresoEgreso::class, 'registrado_por');
    }
}
