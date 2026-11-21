<?php

namespace App\Modules\Seguridad\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de acciones auditadas (02/RF-B03).
 */
class AccionAuditada extends Model
{
    protected $table = 'accion_auditada';

    protected $fillable = [
        'modulo',
        'entidad',
        'accion',
        'critico',
        'activo',
        'descripcion',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'critico' => 'boolean',
            'activo' => 'boolean',
        ];
    }
}
