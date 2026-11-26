<?php

namespace App\Modules\InscripcionesAtenciones\Models;

use App\Modules\InscripcionesAtenciones\Enums\EstadoAtencion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Entrevista con los padres (módulo 06, submódulo 03).
 *
 * Tabla real del schema: `atencion_entrevista`. No hay soft-delete ni
 * borrado físico: la baja cambia el estado a `retirada`.
 */
class AtencionEntrevista extends Model
{
    protected $table = 'atencion_entrevista';

    protected $fillable = [
        'paciente_id',
        'tutor_id',
        'sucursal_id',
        'servicio_id',
        'tarifa_id',
        'categoria_tarifa_id',
        'fecha',
        'monto_esperado',
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
            'tutor_id' => 'integer',
            'sucursal_id' => 'integer',
            'servicio_id' => 'integer',
            'tarifa_id' => 'integer',
            'categoria_tarifa_id' => 'integer',
            'fecha' => 'date:Y-m-d',
            'monto_esperado' => 'decimal:2',
            'estado' => EstadoAtencion::class,
        ];
    }

    public function diagnosticos(): HasMany
    {
        return $this->hasMany(AtencionDiagnostico::class, 'entrevista_previa_id');
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('atencion_entrevista.estado', EstadoAtencion::ACTIVA->value);
    }

    public function scopeRetiradas(Builder $query): Builder
    {
        return $query->where('atencion_entrevista.estado', EstadoAtencion::RETIRADA->value);
    }

    public function scopePorSucursal(Builder $query, int $sucursalId): Builder
    {
        return $query->where('atencion_entrevista.sucursal_id', $sucursalId);
    }

    public function scopePorPeriodo(Builder $query, string $desde, string $hasta): Builder
    {
        return $query
            ->whereDate('atencion_entrevista.fecha', '>=', $desde)
            ->whereDate('atencion_entrevista.fecha', '<=', $hasta);
    }
}
