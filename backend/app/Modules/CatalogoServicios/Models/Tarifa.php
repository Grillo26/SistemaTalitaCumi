<?php

namespace App\Modules\CatalogoServicios\Models;

use App\Modules\Organizacion\Models\Branch;
use Database\Factories\TarifaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tarifa con vigencia y sucursal opcional (RF-T03 a RF-T12).
 *
 * `sucursal_id = NULL` significa tarifa global. `activo` es una bandera
 * técnica; el cierre se expresa con `vigente_hasta`.
 */
class Tarifa extends Model
{
    /** @use HasFactory<TarifaFactory> */
    use HasFactory;

    protected $table = 'tarifa';

    protected $fillable = [
        'servicio_id',
        'categoria_tarifa_id',
        'sucursal_id',
        'monto',
        'vigente_desde',
        'vigente_hasta',
        'activo',
        'version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'servicio_id' => 'integer',
            'categoria_tarifa_id' => 'integer',
            'sucursal_id' => 'integer',
            'monto' => 'decimal:2',
            'vigente_desde' => 'date:Y-m-d',
            'vigente_hasta' => 'date:Y-m-d',
            'activo' => 'boolean',
            'version' => 'integer',
        ];
    }

    protected static function newFactory(): TarifaFactory
    {
        return TarifaFactory::new();
    }

    public function scopeBuscar(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.mb_strtolower($term).'%';

        return $query->whereHas('servicio', static function (Builder $service) use ($like): void {
            $service->whereRaw('lower(servicio.nombre) like ?', [$like]);
        });
    }

    public function scopeServicio(Builder $query, ?int $servicioId): Builder
    {
        return $servicioId === null ? $query : $query->where('tarifa.servicio_id', $servicioId);
    }

    public function scopeCategoria(Builder $query, ?int $categoriaId): Builder
    {
        return $categoriaId === null
            ? $query
            : $query->where('tarifa.categoria_tarifa_id', $categoriaId);
    }

    public function scopeSucursalExacta(Builder $query, ?int $sucursalId): Builder
    {
        return $sucursalId === null
            ? $query->whereNull('sucursal_id')
            : $query->where('sucursal_id', $sucursalId);
    }

    public function scopeVigentes(Builder $query, ?string $fecha = null): Builder
    {
        $fecha ??= now()->toDateString();

        return $query
            ->where('tarifa.activo', true)
            ->where('tarifa.vigente_desde', '<=', $fecha)
            ->where(static function (Builder $query) use ($fecha): void {
                $query->whereNull('tarifa.vigente_hasta')
                    ->orWhere('tarifa.vigente_hasta', '>=', $fecha);
            });
    }

    public function scopeFuturas(Builder $query, ?string $fecha = null): Builder
    {
        $fecha ??= now()->toDateString();

        return $query->where('tarifa.vigente_desde', '>', $fecha);
    }

    public function scopeCerradas(Builder $query, ?string $fecha = null): Builder
    {
        $fecha ??= now()->toDateString();

        return $query
            ->whereNotNull('tarifa.vigente_hasta')
            ->where('tarifa.vigente_hasta', '<', $fecha);
    }

    public function scopeEstadoVigencia(Builder $query, ?string $estado): Builder
    {
        return match ($estado) {
            'vigente' => $query->vigentes(),
            'futura' => $query->futuras(),
            'cerrada' => $query->cerradas(),
            default => $query,
        };
    }

    public function estaCerrada(): bool
    {
        return $this->vigente_hasta !== null
            && $this->vigente_hasta->toDateString() < now()->toDateString();
    }

    public function estaVigente(): bool
    {
        $hoy = now()->toDateString();

        return $this->vigente_desde->toDateString() <= $hoy
            && ($this->vigente_hasta === null || $this->vigente_hasta->toDateString() >= $hoy);
    }

    public function esFutura(): bool
    {
        return $this->vigente_desde->toDateString() > now()->toDateString();
    }

    public function getEstadoVigenciaAttribute(): string
    {
        return match (true) {
            $this->esFutura() => 'futura',
            $this->estaCerrada() => 'cerrada',
            default => 'vigente',
        };
    }

    /**
     * @return BelongsTo<Servicio, $this>
     */
    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    /**
     * @return BelongsTo<CategoriaTarifa, $this>
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaTarifa::class, 'categoria_tarifa_id');
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'sucursal_id');
    }
}
