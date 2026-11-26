<?php

namespace App\Modules\CatalogoServicios\Models;

use Database\Factories\TipoDiagnosticoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Tipo de diagnóstico (RF-D01 a RF-D11).
 *
 * Es un servicio del catálogo (RN-D08) con días estimados y especialidades
 * requeridas con cantidad mínima.
 */
class TipoDiagnostico extends Model
{
    /** @use HasFactory<TipoDiagnosticoFactory> */
    use HasFactory;

    protected $table = 'tipo_diagnostico';

    protected $fillable = [
        'servicio_id',
        'nombre',
        'descripcion',
        'dias_estimados',
        'activo',
        'version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'servicio_id' => 'integer',
            'dias_estimados' => 'integer',
            'activo' => 'boolean',
            'version' => 'integer',
        ];
    }

    protected static function newFactory(): TipoDiagnosticoFactory
    {
        return TipoDiagnosticoFactory::new();
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

        return $query->where(static function (Builder $query) use ($like): void {
            $query
                ->whereRaw('lower(tipo_diagnostico.nombre) like ?', [$like])
                ->orWhereRaw('lower(coalesce(tipo_diagnostico.descripcion, \'\')) like ?', [$like]);
        });
    }

    public function scopeEstado(Builder $query, ?string $estado): Builder
    {
        return match ($estado) {
            'activo' => $query->where('tipo_diagnostico.activo', true),
            'inactivo' => $query->where('tipo_diagnostico.activo', false),
            default => $query,
        };
    }

    public function scopeEspecialidad(Builder $query, ?int $especialidadId): Builder
    {
        return $especialidadId === null
            ? $query
            : $query->whereHas(
                'especialidades',
                static fn (Builder $especialidades) => $especialidades->whereKey($especialidadId),
            );
    }

    /**
     * @return BelongsTo<Servicio, $this>
     */
    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    /**
     * @return BelongsToMany<Especialidad, $this>
     */
    public function especialidades(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Modules\Profesionales\Models\Especialidad::class,
            'tipo_diagnostico_profesional',
            'tipo_diagnostico_id',
            'especialidad_id',
        )->withPivot('cantidad_minima');
    }
}
