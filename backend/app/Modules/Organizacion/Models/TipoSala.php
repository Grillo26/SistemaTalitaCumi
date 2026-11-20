<?php

namespace App\Modules\Organizacion\Models;

use Database\Factories\TipoSalaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tipo de sala global (RF-001/RF-002).
 */
class TipoSala extends Model
{
    /** @use HasFactory<TipoSalaFactory> */
    use HasFactory;

    protected $table = 'tipo_sala';

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

    protected static function newFactory(): TipoSalaFactory
    {
        return TipoSalaFactory::new();
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
