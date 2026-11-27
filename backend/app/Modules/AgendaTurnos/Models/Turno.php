<?php

namespace App\Modules\AgendaTurnos\Models;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\Casts\HoraCast;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use Database\Factories\TurnoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Turno de agenda (módulo 07).
 *
 * 7.1 lo dejó como modelo de lectura; 7.2 escribe turnos por generación y
 * regeneración. 7.3 escribe reprogramación y cancelación individual/masiva.
 * La asistencia pertenece a 7.4.
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
            'inscripcion_sala_id' => 'integer',
            'lote_generacion_id' => 'integer',
            'cancelado_por_lote_id' => 'integer',
            'cancelado_en' => 'datetime',
            'motivo_turno_id' => 'integer',
            'consume_saldo' => 'boolean',
            'cancelado_por_usuario_id' => 'integer',
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

    public function inscripcionSala(): BelongsTo
    {
        return $this->belongsTo(InscripcionSala::class, 'inscripcion_sala_id');
    }

    public function loteGeneracion(): BelongsTo
    {
        return $this->belongsTo(TurnoLoteGeneracion::class, 'lote_generacion_id');
    }

    public function canceladoPorLote(): BelongsTo
    {
        return $this->belongsTo(TurnoLoteGeneracion::class, 'cancelado_por_lote_id');
    }

    public function motivoTurno(): BelongsTo
    {
        return $this->belongsTo(MotivoTurno::class, 'motivo_turno_id');
    }

    public function canceladoPorUsuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelado_por_usuario_id');
    }

    public function scopeFuturos(Builder $query, string $hoy, string $horaActual): Builder
    {
        return $query->where(static function (Builder $sub) use ($hoy, $horaActual): void {
            $sub->where('fecha', '>', $hoy)
                ->orWhere(static function (Builder $today) use ($hoy, $horaActual): void {
                    $today->where('fecha', $hoy)
                        ->where('hora_inicio', '>', $horaActual);
                });
        });
    }

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->whereNotIn('estado', [
            EstadoTurno::CANCELADO->value,
            EstadoTurno::REPROGRAMADO->value,
        ]);
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
