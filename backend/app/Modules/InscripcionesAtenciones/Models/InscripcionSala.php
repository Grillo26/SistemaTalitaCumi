<?php

namespace App\Modules\InscripcionesAtenciones\Models;

use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcionSala;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Inscripción a sala grupal (módulo 06, submódulo 01).
 *
 * Tabla real del schema: `inscripcion_sala`. No hay soft-delete ni borrado
 * físico: la baja cambia el estado a `retirada`.
 */
class InscripcionSala extends Model
{
    protected $table = 'inscripcion_sala';

    protected $fillable = [
        'paciente_id',
        'tutor_responsable_id',
        'sala_id',
        'sucursal_id',
        'servicio_id',
        'tarifa_id',
        'categoria_tarifa_id',
        'fecha_inicio',
        'fecha_fin',
        'monto_esperado',
        'estado',
        'inscripcion_origen_id',
        'observaciones',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'paciente_id' => 'integer',
            'tutor_responsable_id' => 'integer',
            'sala_id' => 'integer',
            'sucursal_id' => 'integer',
            'servicio_id' => 'integer',
            'tarifa_id' => 'integer',
            'categoria_tarifa_id' => 'integer',
            'fecha_inicio' => 'date:Y-m-d',
            'fecha_fin' => 'date:Y-m-d',
            'monto_esperado' => 'decimal:2',
            'estado' => EstadoInscripcionSala::class,
            'inscripcion_origen_id' => 'integer',
        ];
    }

    public function inscripcionOrigen(): BelongsTo
    {
        return $this->belongsTo(self::class, 'inscripcion_origen_id');
    }

    public function renovaciones(): HasMany
    {
        return $this->hasMany(self::class, 'inscripcion_origen_id');
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('estado', EstadoInscripcionSala::ACTIVA->value);
    }

    public function scopeRetiradas(Builder $query): Builder
    {
        return $query->where('estado', EstadoInscripcionSala::RETIRADA->value);
    }

    public function scopePorSucursal(Builder $query, int $sucursalId): Builder
    {
        return $query->where('sucursal_id', $sucursalId);
    }

    public function scopePorPeriodo(Builder $query, string $desde, string $hasta): Builder
    {
        return $query
            ->whereDate('fecha_inicio', '<=', $hasta)
            ->whereDate('fecha_fin', '>=', $desde);
    }
}
