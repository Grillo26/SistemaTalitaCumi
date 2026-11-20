<?php

namespace App\Modules\Organizacion\Models;

use Database\Factories\SalaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sala grupal (RF-004/RF-005 / RN-001/RD-004).
 */
class Sala extends Model
{
    /** @use HasFactory<SalaFactory> */
    use HasFactory;

    protected $table = 'sala';

    protected $fillable = [
        'sucursal_id',
        'area_id',
        'tipo_sala_id',
        'turno_catalogo_id',
        'nombre',
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
            'activo' => 'boolean',
            'version' => 'integer',
        ];
    }

    protected static function newFactory(): SalaFactory
    {
        return SalaFactory::new();
    }

    public function getEstadoAttribute(): string
    {
        return $this->activo ? 'activa' : 'inactiva';
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'sucursal_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function tipoSala(): BelongsTo
    {
        return $this->belongsTo(TipoSala::class, 'tipo_sala_id');
    }

    public function turnoCatalogo(): BelongsTo
    {
        return $this->belongsTo(TurnoCatalogo::class, 'turno_catalogo_id');
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
}
