<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AfiliadoDocumento extends Model
{
    use HasFactory;

    protected $table = 'afiliado_documentos';

    protected $fillable = [
        'afiliado_id',
        'tipo_documento',
        'nombre_archivo',
        'archivo_path',
        'observacion',
        'subido_por',
    ];

    public function afiliado()
    {
        return $this->belongsTo(Afiliado::class);
    }

    public function usuarioSubio()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }
}
