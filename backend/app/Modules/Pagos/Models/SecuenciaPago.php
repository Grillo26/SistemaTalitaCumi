<?php

namespace App\Modules\Pagos\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Último correlativo emitido por sucursal (RF-002 / RN-005).
 *
 * Se bloquea con `lockForUpdate()` dentro de la transacción del registro.
 */
class SecuenciaPago extends Model
{
    protected $table = 'secuencia_pago';

    protected $primaryKey = 'sucursal_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'sucursal_id',
        'ultimo_numero',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sucursal_id' => 'integer',
            'ultimo_numero' => 'integer',
        ];
    }
}
