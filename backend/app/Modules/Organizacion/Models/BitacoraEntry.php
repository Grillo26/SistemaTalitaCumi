<?php

namespace App\Modules\Organizacion\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Entrada de bitácora (solo inserción).
 *
 * La tabla no tiene `updated_at`; la fecha la pone la base de datos.
 */
class BitacoraEntry extends Model
{
    protected $table = 'bitacora';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'sucursal_id',
        'accion',
        'entidad',
        'entidad_id',
        'datos_anteriores',
        'datos_nuevos',
        'motivo',
        'ip',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'datos_anteriores' => 'array',
            'datos_nuevos' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
