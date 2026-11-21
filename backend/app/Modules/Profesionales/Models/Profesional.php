<?php

namespace App\Modules\Profesionales\Models;

use App\Modules\Organizacion\Models\Branch;
use Database\Factories\ProfesionalFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Profesional — directorio global del módulo 03.
 *
 * No es usuario del sistema (03/RN-19): no tiene cuenta, rol Spatie ni FK a
 * users. La tabla usa `activo` (boolean) y no SoftDeletes.
 */
class Profesional extends Model
{
    /** @use HasFactory<ProfesionalFactory> */
    use HasFactory;

    protected $table = 'profesional';

    protected $fillable = [
        'nombre_completo',
        'ci',
        'telefono',
        'telefono_2',
        'email',
        'matricula',
        'activo',
        'version',
        'motivo_estado',
        'estado_cambiado_en',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'version' => 'integer',
            'estado_cambiado_en' => 'datetime',
        ];
    }

    protected static function newFactory(): ProfesionalFactory
    {
        return ProfesionalFactory::new();
    }

    public function getEstadoAttribute(): string
    {
        return $this->activo ? 'activo' : 'inactivo';
    }

    public function relacionesEspecialidad(): HasMany
    {
        return $this->hasMany(ProfesionalEspecialidad::class);
    }

    public function especialidades(): BelongsToMany
    {
        return $this->belongsToMany(
            Especialidad::class,
            'profesional_especialidad',
            'profesional_id',
            'especialidad_id',
        )
            ->withPivot(['id', 'tipo', 'activo'])
            ->withTimestamps()
            ->wherePivot('activo', true);
    }

    public function relacionesSucursal(): HasMany
    {
        return $this->hasMany(ProfesionalSucursal::class);
    }

    public function sucursales(): BelongsToMany
    {
        return $this->belongsToMany(
            Branch::class,
            'profesional_sucursal',
            'profesional_id',
            'sucursal_id',
        )
            ->withPivot(['id', 'activo', 'motivo', 'version'])
            ->withTimestamps()
            ->wherePivot('activo', true);
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
                ->whereRaw('lower(nombre_completo) like ?', [$like])
                ->orWhereRaw('lower(ci) like ?', [$like])
                ->orWhereRaw('lower(coalesce(email, \'\')) like ?', [$like]);
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

    public function scopeEspecialidad(Builder $query, ?int $especialidadId): Builder
    {
        if ($especialidadId === null) {
            return $query;
        }

        return $query->whereHas(
            'relacionesEspecialidad',
            static fn (Builder $relaciones) => $relaciones
                ->where('activo', true)
                ->where('especialidad_id', $especialidadId),
        );
    }

    public function scopeSucursal(Builder $query, ?int $sucursalId): Builder
    {
        if ($sucursalId === null) {
            return $query;
        }

        return $query->whereHas(
            'relacionesSucursal',
            static fn (Builder $relaciones) => $relaciones
                ->where('activo', true)
                ->where('sucursal_id', $sucursalId),
        );
    }
}
