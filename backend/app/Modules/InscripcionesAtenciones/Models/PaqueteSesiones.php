<?php

namespace App\Modules\InscripcionesAtenciones\Models;

use App\Modules\InscripcionesAtenciones\Casts\DiasSemanaCast;
use App\Modules\InscripcionesAtenciones\Enums\EstadoPaqueteSesiones;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Paquete de sesiones individuales (módulo 06, submódulo 02).
 *
 * Tabla real del schema: `paquete_sesiones`. No hay soft-delete ni borrado
 * físico: la baja cambia el estado a `retirado`.
 */
class PaqueteSesiones extends Model
{
    protected $table = 'paquete_sesiones';

    protected $fillable = [
        'paciente_id',
        'tutor_responsable_id',
        'sucursal_id',
        'servicio_id',
        'profesional_id',
        'tarifa_id',
        'categoria_tarifa_id',
        'cantidad_sesiones',
        'dias_semana',
        'hora',
        'duracion_minutos',
        'fecha_inicio',
        'fecha_fin',
        'monto_esperado',
        'estado',
        'paquete_origen_id',
        'observaciones',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'paciente_id' => 'integer',
            'tutor_responsable_id' => 'integer',
            'sucursal_id' => 'integer',
            'servicio_id' => 'integer',
            'profesional_id' => 'integer',
            'tarifa_id' => 'integer',
            'categoria_tarifa_id' => 'integer',
            'cantidad_sesiones' => 'integer',
            'dias_semana' => DiasSemanaCast::class,
            'duracion_minutos' => 'integer',
            'fecha_inicio' => 'date:Y-m-d',
            'fecha_fin' => 'date:Y-m-d',
            'monto_esperado' => 'decimal:2',
            'estado' => EstadoPaqueteSesiones::class,
            'paquete_origen_id' => 'integer',
        ];
    }

    public function paqueteOrigen(): BelongsTo
    {
        return $this->belongsTo(self::class, 'paquete_origen_id');
    }

    public function renovaciones(): HasMany
    {
        return $this->hasMany(self::class, 'paquete_origen_id');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', EstadoPaqueteSesiones::ACTIVO->value);
    }

    public function scopeRetirados(Builder $query): Builder
    {
        return $query->where('estado', EstadoPaqueteSesiones::RETIRADO->value);
    }

    public function scopePorSucursal(Builder $query, int $sucursalId): Builder
    {
        return $query->where('sucursal_id', $sucursalId);
    }

    public function scopePorPeriodo(Builder $query, string $desde, string $hasta): Builder
    {
        return $query
            ->whereDate('fecha_inicio', '<=', $hasta)
            ->whereDate('fecha_fin', '>=', $desde);
    }
}
