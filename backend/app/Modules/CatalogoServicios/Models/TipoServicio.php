<?php

namespace App\Modules\CatalogoServicios\Models;

use Database\Factories\TipoServicioFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tipo de servicio configurable (RN-C09).
 */
class TipoServicio extends Model
{
    /** @use HasFactory<TipoServicioFactory> */
    use HasFactory;

    protected $table = 'tipo_servicio';

    protected $fillable = [
        'nombre',
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

    protected static function newFactory(): TipoServicioFactory
    {
        return TipoServicioFactory::new();
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

        return $query->whereRaw('lower(nombre) like ?', ['%'.mb_strtolower($term).'%']);
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
        return $this->hasMany(Servicio::class, 'tipo_servicio_id');
    }
}
