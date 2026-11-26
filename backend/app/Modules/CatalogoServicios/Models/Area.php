<?php

namespace App\Modules\CatalogoServicios\Models;

use Database\Factories\CatalogoAreaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Área global del catálogo (RF-C01 a RF-C04).
 *
 * La tabla `area` nació en el módulo 01.02 como prerequisito de solo lectura
 * de `sala.area_id`. El módulo 05 es el dueño funcional del CRUD y del color.
 * El modelo de Organización se conserva para no romper ese submódulo.
 */
class Area extends Model
{
    /** @use HasFactory<CatalogoAreaFactory> */
    use HasFactory;

    protected $table = 'area';

    protected $fillable = [
        'nombre',
        'descripcion',
        'color',
        'activo',
        'version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'version' => 'integer',
        ];
    }

    protected static function newFactory(): CatalogoAreaFactory
    {
        return CatalogoAreaFactory::new();
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
                ->whereRaw('lower(nombre) like ?', [$like])
                ->orWhereRaw('lower(coalesce(descripcion, \'\')) like ?', [$like]);
        });
    }

    public function scopeEstado(Builder $query, ?string $estado): Builder
    {
        return match ($estado) {
            'activo' => $query->where('activo', true),
            'inactivo' => $query->where('activo', false),
            default => $query,
        };
    }

    /**
     * @return HasMany<Servicio, $this>
     */
    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'area_id');
    }
}
