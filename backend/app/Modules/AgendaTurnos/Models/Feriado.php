<?php

namespace App\Modules\AgendaTurnos\Models;

use App\Modules\Organizacion\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Feriado configurable (módulo 07, submódulo 02 — RF-016).
 *
 * `sucursal_id` null = feriado global. No hay precarga: la generación
 * funciona igual si la tabla está vacía.
 */
class Feriado extends Model
{
    protected $table = 'feriado';

    protected $fillable = [
        'fecha',
        'descripcion',
        'sucursal_id',
        'activo',
        'usuario_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
            'sucursal_id' => 'integer',
            'activo' => 'boolean',
            'usuario_id' => 'integer',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'sucursal_id');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeParaSucursal(Builder $query, int $sucursalId): Builder
    {
        return $query->where(
            static fn (Builder $sub): Builder => $sub
                ->whereNull('sucursal_id')
                ->orWhere('sucursal_id', $sucursalId),
        );
    }

    public function scopeEnRango(Builder $query, string $desde, string $hasta): Builder
    {
        return $query->whereBetween('fecha', [$desde, $hasta]);
    }
}
