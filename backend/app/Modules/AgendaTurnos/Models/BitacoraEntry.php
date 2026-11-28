<?php

namespace App\Modules\AgendaTurnos\Models;

use App\Models\User;
use App\Modules\Organizacion\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entrada de bitácora del módulo 07 (solo inserción).
 *
 * Reutiliza la tabla `bitacora` y su trigger de inmutabilidad.
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

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'sucursal_id');
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class, 'entidad_id');
    }
}
