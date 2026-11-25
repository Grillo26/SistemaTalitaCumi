<?php

namespace App\Modules\Pacientes\Models;

use App\Modules\Organizacion\Models\Branch;
use App\Modules\Pacientes\Support\PacienteNormalizer;
use Database\Factories\TutorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tutor — maestro global de 04.02.
 *
 * No usa SoftDeletes: se desactiva y conserva el historial. La sucursal de
 * registro (home branch) define su visibilidad base; las sucursales de los
 * pacientes asociados también otorgan visibilidad.
 */
class Tutor extends Model
{
    /** @use HasFactory<TutorFactory> */
    use HasFactory;

    protected $table = 'tutor';

    protected $fillable = [
        'sucursal_registro_id',
        'nombre_completo',
        'ci',
        'telefono',
        'telefono_alternativo',
        'email',
        'direccion',
        'parentesco',
        'parentesco_id',
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

    protected static function booted(): void
    {
        static::saving(function (Tutor $tutor): void {
            $tutor->nombre_completo_normalizado = PacienteNormalizer::key($tutor->nombre_completo);
            $tutor->documento_normalizado = PacienteNormalizer::documentKey($tutor->ci);
            $tutor->telefono_normalizado = PacienteNormalizer::phoneKey($tutor->telefono);
        });
    }

    protected static function newFactory(): TutorFactory
    {
        return TutorFactory::new();
    }

    public function getEstadoAttribute(): string
    {
        return $this->activo ? 'activo' : 'inactivo';
    }

    public function sucursalRegistro(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'sucursal_registro_id');
    }

    /**
     * Catálogo de parentesco. Se evita el nombre `parentesco` porque la tabla
     * conserva la columna legacy del mismo nombre (texto libre).
     */
    public function parentescoCatalogo(): BelongsTo
    {
        return $this->belongsTo(Parentesco::class, 'parentesco_id');
    }

    public function relacionesPaciente(): HasMany
    {
        return $this->hasMany(PacienteTutor::class);
    }

    public function pacientes(): BelongsToMany
    {
        return $this->belongsToMany(
            Paciente::class,
            'paciente_tutor',
            'tutor_id',
            'paciente_id',
        )->withPivot(['parentesco_id', 'es_principal']);
    }

    public function scopeBuscar(Builder $query, ?string $term): Builder
    {
        $term = PacienteNormalizer::key($term);

        if ($term === '') {
            return $query;
        }

        $tokens = array_values(array_filter(explode(' ', $term), static fn (string $token): bool => $token !== ''));
        $phoneTerm = PacienteNormalizer::phoneKey($term);

        return $query->where(function (Builder $query) use ($term, $tokens, $phoneTerm): void {
            $query
                ->where('documento_normalizado', 'like', $term.'%')
                ->orWhere(function (Builder $query) use ($tokens): void {
                    foreach ($tokens as $token) {
                        $query->where('nombre_completo_normalizado', 'like', '%'.$token.'%');
                    }
                });

            if ($phoneTerm !== '') {
                $query->orWhere('telefono_normalizado', 'like', '%'.$phoneTerm.'%');
            }
        });
    }

    public function scopeActivo(Builder $query, ?bool $activo = true): Builder
    {
        if ($activo === null) {
            return $query;
        }

        return $query->where('activo', $activo);
    }
}
