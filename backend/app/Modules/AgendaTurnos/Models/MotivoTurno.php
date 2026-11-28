<?php

namespace App\Modules\AgendaTurnos\Models;

use App\Modules\AgendaTurnos\Enums\OrigenMotivoTurno;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Motivo configurable de reprogramación o cancelación (RF-003 / RNF-011).
 *
 * `origen` y `descuenta_saldo` son nullable a propósito: el negocio debe
 * confirmarlos. Mientras no estén configurados, la cancelación no consume
 * saldo y el preview lo informa.
 */
class MotivoTurno extends Model
{
    protected $table = 'motivo_turno';

    protected $fillable = [
        'codigo',
        'nombre',
        'aplica_reprogramar',
        'aplica_cancelar',
        'origen',
        'descuenta_saldo',
        'requiere_texto',
        'activo',
        'orden',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aplica_reprogramar' => 'boolean',
            'aplica_cancelar' => 'boolean',
            'origen' => OrigenMotivoTurno::class,
            'descuenta_saldo' => 'boolean',
            'requiere_texto' => 'boolean',
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeParaReprogramar(Builder $query): Builder
    {
        return $query->where('aplica_reprogramar', true);
    }

    public function scopeParaCancelar(Builder $query): Builder
    {
        return $query->where('aplica_cancelar', true);
    }

    public function scopeOrdenados(Builder $query): Builder
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }
}
