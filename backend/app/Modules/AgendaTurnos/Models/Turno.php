<?php

namespace App\Modules\AgendaTurnos\Models;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\Casts\HoraCast;
use Database\Factories\TurnoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Turno de agenda (módulo 07).
 *
 * En este submódulo el modelo es de LECTURA. Las escrituras (programación,
 * reprogramación, cancelación y asistencia) pertenecen a 7.2 a 7.5.
 */
class Turno extends Model
{
    /** @use HasFactory<TurnoFactory> */
    use HasFactory;

    protected $table = 'turno';

    protected $guarded = ['id'];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
            'hora_inicio' => HoraCast::class,
            'hora_fin' => HoraCast::class,
            'duracion_minutos' => 'integer',
            'estado' => EstadoTurno::class,
            'asistencia_registrada_en' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function newFactory(): TurnoFactory
    {
        return TurnoFactory::new();
    }

    public function turnoOrigen(): BelongsTo
    {
        return $this->belongsTo(self::class, 'turno_origen_id');
    }

    public function reprogramaciones(): HasMany
    {
        return $this->hasMany(self::class, 'turno_origen_id');
    }

    public function scopeDeSucursal(Builder $query, int|array $sucursalIds): Builder
    {
        return is_array($sucursalIds)
            ? $query->whereIn('sucursal_id', $sucursalIds)
            : $query->where('sucursal_id', $sucursalIds);
    }

    public function scopeEnRango(Builder $query, string $desde, string $hasta): Builder
    {
        return $query->whereBetween('fecha', [$desde, $hasta]);
    }

    public function scopePorEstado(Builder $query, EstadoTurno|array $estado): Builder
    {
        return is_array($estado)
            ? $query->whereIn('estado', array_map(
                static fn (EstadoTurno $item): string => $item->value,
                $estado,
            ))
            : $query->where('estado', $estado->value);
    }

    public function scopePorProfesional(Builder $query, ?int $profesionalId, bool $sinAsignar = false): Builder
    {
        if ($sinAsignar) {
            return $query->whereNull('profesional_id');
        }

        return $profesionalId === null
            ? $query
            : $query->where('profesional_id', $profesionalId);
    }

    public function scopePorSala(Builder $query, ?int $salaId, bool $sinAsignar = false): Builder
    {
        if ($sinAsignar) {
            return $query->whereNull('sala_id');
        }

        return $salaId === null
            ? $query
            : $query->where('sala_id', $salaId);
    }

    /**
     * @param  list<int>  $servicioIds
     */
    public function scopePorServicios(Builder $query, array $servicioIds): Builder
    {
        return $servicioIds === []
            ? $query->whereRaw('1 = 0')
            : $query->whereIn('servicio_id', $servicioIds);
    }

    public function scopeOrdenado(Builder $query): Builder
    {
        return $query
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->orderBy('id');
    }
}
