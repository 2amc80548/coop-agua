<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conexion extends Model
{
    protected $table = 'conexiones';

    // ---------------------------------------------------------------
    // Constantes: Tipos de Conexión
    // ---------------------------------------------------------------
    const TIPO_DOMICILIARIA  = 'domiciliaria';
    const TIPO_COMERCIAL     = 'comercial';
    const TIPO_INSTITUCIONAL = 'institucional';
    const TIPO_OTRO          = 'otro';

    // ---------------------------------------------------------------
    // Constantes: Estados de Conexión
    // ---------------------------------------------------------------
    const ESTADO_ACTIVO     = 'activo';
    const ESTADO_SUSPENDIDO = 'suspendido';
    const ESTADO_ELIMINADO  = 'eliminado';

    protected $fillable = [
        'codigo_medidor',
        'afiliado_id',
        'estado',
        'direccion',
        'zona_id',
        'fecha_instalacion',
        'tipo_conexion',
    ];

    /**
     * Conversión de tipos.
     * Sin el cast de fecha_instalacion, Carbon no puede operar sobre él
     * y llega como string al frontend Vue.
     */
    protected $casts = [
        'fecha_instalacion' => 'date:Y-m-d',
    ];

    // ---------------------------------------------------------------
    // Relaciones Eloquent
    // ---------------------------------------------------------------

    public function afiliado(): BelongsTo
    {
        return $this->belongsTo(Afiliado::class, 'afiliado_id');
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'zona_id');
    }

    public function lecturas(): HasMany
    {
        return $this->hasMany(Lectura::class, 'conexion_id');
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class, 'conexion_id');
    }

    // NOTA: La relación accesosAutorizados() fue eliminada (Hallazgo 2.2).
    // El modelo AccesoAutorizado no existe en el sistema y causaría
    // una excepción fatal si se intentara cargar esa relación.
    // Si en el futuro se implementa esa funcionalidad, agregar aquí
    // la relación y crear el modelo y migración correspondientes.
}
