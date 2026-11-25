<?php

namespace App\Modules\Pacientes\Models;

use App\Models\User;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Pacientes\Enums\EstadoFichaIngreso;
use Database\Factories\FichaIngresoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ficha de ingreso administrativa (04.03 Fase A).
 *
 * No es la entrevista terapéutica ni el registro clínico. Una ficha abierta
 * por paciente; las anteriores se archivan y conservan.
 */
class FichaIngreso extends Model
{
    /** @use HasFactory<FichaIngresoFactory> */
    use HasFactory;

    protected $table = 'ficha_ingreso';

    protected $fillable = [
        'paciente_id',
        'sucursal_ingreso_id',
        'tutor_principal_id',
        'motivo_consulta_inicial',
        'fecha_ingreso',
        'composicion_familiar',
        'antecedentes_familiares',
        'observaciones',
        'derivado_por',
        'derivado_por_institucion',
        'derivado_por_contacto',
        'estado',
        'estado_anterior',
        'version',
        'usuario_registro_id',
        'completada_en',
        'completada_por_id',
        'archivada_en',
        'archivada_por_id',
        'motivo_archivado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
            'estado' => EstadoFichaIngreso::class,
            'version' => 'integer',
            'completada_en' => 'datetime',
            'archivada_en' => 'datetime',
        ];
    }

    protected static function newFactory(): FichaIngresoFactory
    {
        return FichaIngresoFactory::new();
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function sucursalIngreso(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'sucursal_ingreso_id');
    }

    public function tutorPrincipal(): BelongsTo
    {
        return $this->belongsTo(Tutor::class, 'tutor_principal_id');
    }

    public function usuarioRegistro(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_registro_id');
    }

    public function completadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completada_por_id');
    }

    public function archivadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archivada_por_id');
    }

    public function revisiones(): HasMany
    {
        return $this->hasMany(FichaIngresoRevision::class);
    }

    public function consentimientos(): HasMany
    {
        return $this->hasMany(Consentimiento::class);
    }

    public function observacionesFechadas(): HasMany
    {
        return $this->hasMany(FichaIngresoObservacion::class, 'ficha_ingreso_id');
    }

    public function scopeVigente(Builder $query): Builder
    {
        return $query->where('estado', '!=', EstadoFichaIngreso::ARCHIVADA->value);
    }

    public function estaArchivada(): bool
    {
        return $this->estado === EstadoFichaIngreso::ARCHIVADA;
    }
}
