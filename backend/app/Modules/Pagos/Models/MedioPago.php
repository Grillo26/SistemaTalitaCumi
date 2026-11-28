<?php

namespace App\Modules\Pagos\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de medios de pago (RF-005, RN-002, RNF-011). El código es estable.
 */
class MedioPago extends Model
{
    protected $table = 'medio_pago';

    protected $fillable = [
        'codigo',
        'nombre',
        'activo',
        'orden',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('orden')->orderBy('id');
    }
}
