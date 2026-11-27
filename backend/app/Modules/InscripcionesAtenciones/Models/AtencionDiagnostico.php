<?php

namespace App\Modules\InscripcionesAtenciones\Models;

use App\Modules\InscripcionesAtenciones\Enums\EstadoAtencion;
use App\Modules\Profesionales\Models\Profesional;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Diagnóstico o evaluación como atención (módulo 06, submódulo 03).
 *
 * Tabla real del schema: `atencion_diagnostico`. `estado_general` queda
 * reservado para la etapa 2 (Evaluaciones y Resultados); este submódulo no
 * almacena datos clínicos.
 */
class AtencionDiagnostico extends Model
{
    protected $table = 'atencion_diagnostico';

    protected $fillable = [
        'paciente_id',
        'sucursal_id',
        'servicio_id',
        'tipo_diagnostico_id',
        'tarifa_id',
        'categoria_tarifa_id',
        'fecha_inicio',
        'fecha_fin',
        'monto_esperado',
        'entrevista_previa_id',
        'omitio_entrevista',
        'motivo_omision_entrevista',
        'estado',
        'notas_admin',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'paciente_id' => 'integer',
            'sucursal_id' => 'integer',
            'servicio_id' => 'integer',
            'tipo_diagnostico_id' => 'integer',
            'tarifa_id' => 'integer',
            'categoria_tarifa_id' => 'integer',
            'fecha_inicio' => 'date:Y-m-d',
            'fecha_fin' => 'date:Y-m-d',
            'monto_esperado' => 'decimal:2',
            'entrevista_previa_id' => 'integer',
            'omitio_entrevista' => 'boolean',
            'estado' => EstadoAtencion::class,
        ];
    }

    public function entrevistaPrevia(): BelongsTo
    {
        return $this->belongsTo(AtencionEntrevista::class, 'entrevista_previa_id');
    }

    /**
     * @return BelongsToMany<Profesional, $this>
     */
    public function profesionales(): BelongsToMany
    {
        return $this->belongsToMany(
            Profesional::class,
            'atencion_diagnostico_profesional',
            'atencion_diagnostico_id',
            'profesional_id',
        );
    }

    public function requisitos(): HasMany
    {
        return $this->hasMany(AtencionDiagnosticoRequisito::class, 'atencion_diagnostico_id');
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('atencion_diagnostico.estado', EstadoAtencion::ACTIVA->value);
    }

    public function scopeRetiradas(Builder $query): Builder
    {
        return $query->where('atencion_diagnostico.estado', EstadoAtencion::RETIRADA->value);
    }

    public function scopePorSucursal(Builder $query, int $sucursalId): Builder
    {
        return $query->where('atencion_diagnostico.sucursal_id', $sucursalId);
    }

    public function scopePorPeriodo(Builder $query, string $desde, string $hasta): Builder
    {
        return $query
            ->whereDate('atencion_diagnostico.fecha_inicio', '<=', $hasta)
            ->whereDate('atencion_diagnostico.fecha_fin', '>=', $desde);
    }
}
