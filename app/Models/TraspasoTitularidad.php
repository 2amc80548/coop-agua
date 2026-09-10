<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TraspasoTitularidad extends Model
{
    use HasFactory;

    protected $table = 'traspasos_titularidad';

    protected $fillable = [
        'conexion_id',
        'afiliado_anterior_id',
        'afiliado_nuevo_id',
        'fecha_traspaso',
        'motivo',
        'registrado_por',
    ];

    public function conexion()
    {
        return $this->belongsTo(Conexion::class);
    }

    public function afiliadoAnterior()
    {
        return $this->belongsTo(Afiliado::class, 'afiliado_anterior_id');
    }

    public function afiliadoNuevo()
    {
        return $this->belongsTo(Afiliado::class, 'afiliado_nuevo_id');
    }

    public function usuarioRegistrado()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
