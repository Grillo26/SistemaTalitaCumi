<?php

namespace App\Modules\Profesionales\Models;

use Database\Factories\EspecialidadFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Especialidad — catálogo global del módulo 03.
 *
 * La tabla real es `especialidad` y usa `activo` (boolean). No hay
 * SoftDeletes: se desactiva con motivo y conserva el historial.
 */
class Especialidad extends Model
{
    /** @use HasFactory<EspecialidadFactory> */
    use HasFactory;

    protected $table = 'especialidad';

    protected $fillable = [
        'nombre',
        'descripcion',
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

    protected static function newFactory(): EspecialidadFactory
    {
        return EspecialidadFactory::new();
    }

    public function getEstadoAttribute(): string
    {
        return $this->activo ? 'activo' : 'inactivo';
    }

    public function relaciones(): HasMany
    {
        return $this->hasMany(ProfesionalEspecialidad::class);
    }

    public function profesionales(): BelongsToMany
    {
        return $this->belongsToMany(
            Profesional::class,
            'profesional_especialidad',
            'especialidad_id',
            'profesional_id',
        )->withPivot(['id', 'tipo', 'activo'])->withTimestamps();
    }

    public function profesionalesActivos(): BelongsToMany
    {
        return $this->belongsToMany(
            Profesional::class,
            'profesional_especialidad',
            'especialidad_id',
            'profesional_id',
        )
            ->wherePivot('activo', true)
            ->where('profesional.activo', true);
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
}
