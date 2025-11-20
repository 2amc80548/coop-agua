<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Conexion;
use App\Models\Afiliado;
use App\Models\User;
use App\Models\ReclamoTipo;

class Reclamo extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     */
    protected $table = 'reclamos';

    /**
     * Los atributos que se pueden asignar masivamente.
     * (Todos los campos que definimos en la BD).
     */
    protected $fillable = [
        'afiliado_id',
        'user_id', 
        'reclamo_tipo_id',
        'asunto',
        'mensaje_usuario',
        'estado',
        'respuesta_admin',
        'resuelto_por_user_id',
    ];

    /**
     * Conversión de tipos (casts) para que Laravel maneje bien los datos.
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i',
        'updated_at' => 'datetime:Y-m-d H:i',
    ];

    /**
     * El Reclamo pertenece a un Afiliado.
     */

    public function conexion(): BelongsTo   
    {
        return $this->belongsTo(Conexion::class, 'conexion_id');
    }

    public function afiliado(): BelongsTo
    {
        return $this->belongsTo(Afiliado::class, 'afiliado_id');
    }

    /**
     * El Reclamo fue creado por un Usuario.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * El Reclamo tiene un Tipo (del catálogo).
     */
    public function tipo()
    {
        return $this->belongsTo(ReclamoTipo::class, 'reclamo_tipo_id');
    }

    /**
     * El Reclamo fue resuelto por un Usuario (Admin/Secretaria).
     */
    public function resueltoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelto_por_user_id');
    }
}