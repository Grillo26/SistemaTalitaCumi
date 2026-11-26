<?php

namespace App\Modules\CatalogoServicios\Models;

use Database\Factories\ServicioFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Servicio global del catálogo (RF-C05 a RF-C13).
 *
 * Pertenece a exactamente un área (RN-C04) y a un tipo configurable.
 */
class Servicio extends Model
{
    /** @use HasFactory<ServicioFactory> */
    use HasFactory;

    protected $table = 'servicio';

    protected $fillable = [
        'area_id',
        'tipo_servicio_id',
        'nombre',
        'descripcion',
        'duracion_minutos',
        'activo',
        'version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'area_id' => 'integer',
            'tipo_servicio_id' => 'integer',
            'duracion_minutos' => 'integer',
            'activo' => 'boolean',
            'version' => 'integer',
        ];
    }

    protected static function newFactory(): ServicioFactory
    {
        return ServicioFactory::new();
    }

    public function getEstadoAttribute(): string
    {
        return $this->activo ? 'activo' : 'inactivo';
    }

    public function scopeBuscar(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.mb_strtolower($term).'%';

        return $query->where(function (Builder $query) use ($like): void {
            $query
                ->whereRaw('lower(servicio.nombre) like ?', [$like])
                ->orWhereRaw('lower(coalesce(servicio.descripcion, \'\')) like ?', [$like]);
        });
    }

    public function scopeEstado(Builder $query, ?string $estado): Builder
    {
        return match ($estado) {
            'activo' => $query->where('servicio.activo', true),
            'inactivo' => $query->where('servicio.activo', false),
            default => $query,
        };
    }

    public function scopeArea(Builder $query, ?int $areaId): Builder
    {
        return $areaId === null ? $query : $query->where('servicio.area_id', $areaId);
    }

    public function scopeTipoServicio(Builder $query, ?int $tipoServicioId): Builder
    {
        return $tipoServicioId === null
            ? $query
            : $query->where('servicio.tipo_servicio_id', $tipoServicioId);
    }

    /**
     * @return BelongsTo<Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    /**
     * @return BelongsTo<TipoServicio, $this>
     */
    public function tipoServicio(): BelongsTo
    {
        return $this->belongsTo(TipoServicio::class, 'tipo_servicio_id');
    }
}
