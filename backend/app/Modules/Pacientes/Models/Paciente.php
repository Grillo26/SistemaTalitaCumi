<?php

namespace App\Modules\Pacientes\Models;

use App\Modules\Organizacion\Models\Branch;
use App\Modules\Pacientes\Enums\EstadoPaciente;
use App\Modules\Pacientes\Enums\Sexo;
use App\Modules\Pacientes\Rules\PacienteBusquedaRule;
use App\Modules\Pacientes\Support\PacienteNormalizer;
use Database\Factories\PacienteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Paciente — registro global del módulo 04.01.
 *
 * Una sola fila por paciente. `sucursal_registro_id` es el ancla de
 * visibilidad, no una duplicación por sucursal (04.01/RN-001).
 */
class Paciente extends Model
{
    /** @use HasFactory<PacienteFactory> */
    use HasFactory;

    protected $table = 'paciente';

    protected $fillable = [
        'codigo',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'fecha_nacimiento',
        'sexo',
        'ci',
        'tipo_diagnostico_id',
        'sucursal_registro_id',
        'estado',
        'alergias',
        'condiciones_medicas',
        'observaciones',
        'version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'sexo' => Sexo::class,
            'estado' => EstadoPaciente::class,
            'version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Paciente $paciente): void {
            $paciente->nombre_normalizado = PacienteNormalizer::key($paciente->nombre);
            $paciente->apellido_paterno_normalizado = PacienteNormalizer::key($paciente->apellido_paterno);
            $paciente->apellido_materno_normalizado = PacienteNormalizer::key($paciente->apellido_materno);
            $paciente->nombre_completo_normalizado = PacienteNormalizer::key(
                trim(implode(' ', array_filter([
                    $paciente->nombre,
                    $paciente->apellido_paterno,
                    $paciente->apellido_materno,
                ], static fn (?string $value): bool => filled($value)))),
            );
            $paciente->documento_normalizado = PacienteNormalizer::documentKey($paciente->ci);
        });
    }

    protected static function newFactory(): PacienteFactory
    {
        return PacienteFactory::new();
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->nombre,
            $this->apellido_paterno,
            $this->apellido_materno,
        ], static fn (?string $value): bool => filled($value))));
    }

    public function sucursalRegistro(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'sucursal_registro_id');
    }

    public function relacionesTutor(): HasMany
    {
        return $this->hasMany(PacienteTutor::class);
    }

    public function tutores(): BelongsToMany
    {
        return $this->belongsToMany(
            Tutor::class,
            'paciente_tutor',
            'paciente_id',
            'tutor_id',
        )->withPivot(['parentesco_id', 'es_principal']);
    }

    public function estadoHistorial(): HasMany
    {
        return $this->hasMany(EstadoPacienteHistorial::class);
    }

    public function contactosEmergencia(): HasMany
    {
        return $this->hasMany(ContactoEmergencia::class);
    }

    public function tutorPrincipal(): ?Tutor
    {
        $relacion = $this->relacionesTutor()
            ->where('es_principal', true)
            ->with('tutor')
            ->first();

        return $relacion?->tutor;
    }

    public function tieneTutorPrincipal(): bool
    {
        return $this->relacionesTutor()->where('es_principal', true)->exists();
    }

    /**
     * Búsqueda por nombre, apellido, documento, código y tutor principal
     * activo (04.04/RF-001, 04.04/RF-002, 04.04/RF-003).
     *
     * El teléfono del tutor solo se pasa cuando el actor tiene
     * `tutor.ver_contacto`; la comparación es exacta sobre el teléfono
     * normalizado de 8 dígitos.
     */
    public function scopeBuscar(
        Builder $query,
        ?string $term,
        ?string $telefonoTutor = null,
    ): Builder {
        $term = PacienteBusquedaRule::term($term);

        if ($term === '') {
            return $query;
        }

        $tokens = PacienteBusquedaRule::tokens($term);

        return $query->where(function (Builder $query) use ($term, $tokens, $telefonoTutor): void {
            $query
                ->where('documento_normalizado', 'like', $term.'%')
                ->orWhereRaw('lower(codigo) like ?', ['%'.$term.'%'])
                ->orWhere(function (Builder $query) use ($tokens): void {
                    foreach ($tokens as $token) {
                        $query->where('nombre_completo_normalizado', 'like', '%'.$token.'%');
                    }
                })
                ->orWhere('apellido_paterno_normalizado', 'like', $term.'%')
                ->orWhere('apellido_materno_normalizado', 'like', $term.'%');

            if ($tokens === [] && $telefonoTutor === null) {
                return;
            }

            $query->orWhereHas('relacionesTutor', function (Builder $relacion) use ($tokens, $telefonoTutor): void {
                $relacion
                    ->where('es_principal', true)
                    ->whereHas('tutor', function (Builder $tutor) use ($tokens, $telefonoTutor): void {
                        $tutor->where('activo', true);

                        $tutor->where(function (Builder $tutor) use ($tokens, $telefonoTutor): void {
                            foreach ($tokens as $token) {
                                $tutor->where('nombre_completo_normalizado', 'like', '%'.$token.'%');
                            }

                            if ($telefonoTutor !== null) {
                                $tutor->orWhere('telefono_normalizado', $telefonoTutor);
                            }
                        });
                    });
            });
        });
    }

    public function scopeEstado(Builder $query, ?string $estado): Builder
    {
        if ($estado === null || $estado === '') {
            return $query;
        }

        return $query->where('estado', $estado);
    }

    public function scopeSucursalRegistro(Builder $query, ?int $sucursalId): Builder
    {
        if ($sucursalId === null) {
            return $query;
        }

        return $query->where('sucursal_registro_id', $sucursalId);
    }

    public function scopeTipoDiagnostico(Builder $query, ?int $tipoDiagnosticoId): Builder
    {
        if ($tipoDiagnosticoId === null) {
            return $query;
        }

        return $query->where('tipo_diagnostico_id', $tipoDiagnosticoId);
    }

    public function scopeSinDiagnostico(Builder $query, ?bool $sinDiagnostico): Builder
    {
        if ($sinDiagnostico !== true) {
            return $query;
        }

        return $query->whereNull('tipo_diagnostico_id');
    }
}
