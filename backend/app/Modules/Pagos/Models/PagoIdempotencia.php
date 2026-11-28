<?php

namespace App\Modules\Pagos\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Clave de idempotencia de un registro de pago (RT-001). Solo inserción.
 */
class PagoIdempotencia extends Model
{
    protected $table = 'pago_idempotencia';

    public const UPDATED_AT = null;

    protected $fillable = [
        'usuario_id',
        'sucursal_id',
        'clave',
        'hash_solicitud',
        'grupo_pago_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'usuario_id' => 'integer',
            'sucursal_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
