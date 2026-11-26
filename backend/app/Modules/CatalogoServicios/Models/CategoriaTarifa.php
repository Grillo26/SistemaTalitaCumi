<?php

namespace App\Modules\CatalogoServicios\Models;

use Database\Factories\CategoriaTarifaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Categoría global de tarifa (RF-T01 / RF-T02).
 */
class CategoriaTarifa extends Model
{
    /** @use HasFactory<CategoriaTarifaFactory> */
    use HasFactory;

    protected $table = 'categoria_tarifa';

    protected $fillable = [
        'nombre',
        'descripcion',
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

    protected static function newFactory(): CategoriaTarifaFactory
    {
        return CategoriaTarifaFactory::new();
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
     * @return HasMany<Tarifa, $this>
     */
    public function tarifas(): HasMany
    {
        return $this->hasMany(Tarifa::class, 'categoria_tarifa_id');
    }
}
