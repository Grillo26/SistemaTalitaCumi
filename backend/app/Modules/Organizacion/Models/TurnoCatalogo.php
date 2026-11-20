<?php

namespace App\Modules\Organizacion\Models;

use Database\Factories\TurnoCatalogoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Turno operativo global (RF-003 / RN-002).
 *
 * No confundir con la tabla `turno` de agenda (módulo 07).
 */
class TurnoCatalogo extends Model
{
    /** @use HasFactory<TurnoCatalogoFactory> */
    use HasFactory;

    protected $table = 'turno_catalogo';

    protected $fillable = [
        'nombre',
        'orden',
        'activo',
        'version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'activo' => 'boolean',
            'version' => 'integer',
        ];
    }

    protected static function newFactory(): TurnoCatalogoFactory
    {
        return TurnoCatalogoFactory::new();
    }

    public function getEstadoAttribute(): string
    {
        return $this->activo ? 'activo' : 'inactivo';
    }

    public function salas(): HasMany
    {
        return $this->hasMany(Sala::class);
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
}
