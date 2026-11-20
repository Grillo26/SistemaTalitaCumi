<?php

namespace App\Modules\Organizacion\Models;

use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Sucursal — Módulo 01 Organización y sucursales.
 *
 * La tabla real se llama `sucursal` y usa `activo` (boolean) como estado.
 * No hay SoftDeletes: una sucursal se desactiva, nunca se borra.
 */
class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory;

    protected $table = 'sucursal';

    protected $fillable = [
        'codigo',
        'nombre',
        'direccion',
        'telefono',
        'email',
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

    protected static function newFactory(): BranchFactory
    {
        return BranchFactory::new();
    }

    public function getEstadoAttribute(): string
    {
        return $this->activo ? 'activa' : 'inactiva';
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
                ->whereRaw('lower(codigo) like ?', [$like])
                ->orWhereRaw('lower(nombre) like ?', [$like]);
        });
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
