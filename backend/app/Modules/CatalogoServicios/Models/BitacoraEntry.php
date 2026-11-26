<?php

namespace App\Modules\CatalogoServicios\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entrada de bitácora del módulo 05 (solo inserción).
 *
 * Reutiliza la tabla `bitacora`; no se modifica su estructura ni su trigger.
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
        'modulo',
        'resultado',
        'critico',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'datos_anteriores' => 'array',
            'datos_nuevos' => 'array',
            'critico' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
