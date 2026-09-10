<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'accion',
        'modulo',
        'descripcion',
        'ip_address',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Registra un evento en la bitácora de auditoría.
     */
    public static function log(
        string $accion,
        string $modulo,
        string $descripcion,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        return self::create([
            'user_id'     => auth()->id(),
            'accion'      => $accion,
            'modulo'      => $modulo,
            'descripcion' => $descripcion,
            'ip_address'  => request()->ip(),
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
        ]);
    }
}
