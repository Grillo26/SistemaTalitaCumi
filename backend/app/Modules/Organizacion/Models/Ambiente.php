<?php

namespace App\Modules\Organizacion\Models;

use App\Modules\Organizacion\Enums\UsoAmbiente;
use Database\Factories\AmbienteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ambiente de atención individual (RF-006/RF-007).
 */
class Ambiente extends Model
{
    /** @use HasFactory<AmbienteFactory> */
    use HasFactory;

    protected $table = 'ambiente';

    protected $fillable = [
        'sucursal_id',
        'nombre',
        'uso',
        'observaciones',
        'activo',
        'version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'uso' => UsoAmbiente::class,
            'activo' => 'boolean',
            'version' => 'integer',
        ];
    }

    protected static function newFactory(): AmbienteFactory
    {
        return AmbienteFactory::new();
    }

    public function getEstadoAttribute(): string
    {
        return $this->activo ? 'activa' : 'inactiva';
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'sucursal_id');
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
            'activa' => $query->where('activo', true),
            'inactiva' => $query->where('activo', false),
            default => $query,
        };
    }

    public function scopeUso(Builder $query, ?string $uso): Builder
    {
        return $uso === null || $uso === ''
            ? $query
            : $query->where('uso', $uso);
    }
}
