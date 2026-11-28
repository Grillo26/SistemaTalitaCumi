<?php

namespace App\Modules\AgendaTurnos\Models;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\AccionLoteGeneracion;
use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;
use App\Modules\AgendaTurnos\Support\LoteResultado;
use App\Modules\Organizacion\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lote de generación o regeneración de turnos (módulo 07, submódulo 02).
 *
 * Guarda la clave de idempotencia, el actor y las cantidades del resultado.
 * La cancelación por regeneración se referencia desde `turno.cancelado_por_lote_id`.
 */
class TurnoLoteGeneracion extends Model
{
    protected $table = 'turno_lote_generacion';

    protected $fillable = [
        'tipo_origen',
        'origen_id',
        'sucursal_id',
        'accion',
        'clave_idempotencia',
        'usuario_id',
        'ip',
        'cantidad_creados',
        'cantidad_cancelados',
        'cantidad_preservados',
        'advertencias_confirmadas',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo_origen' => TipoOrigenTurno::class,
            'origen_id' => 'integer',
            'sucursal_id' => 'integer',
            'accion' => AccionLoteGeneracion::class,
            'usuario_id' => 'integer',
            'cantidad_creados' => 'integer',
            'cantidad_cancelados' => 'integer',
            'cantidad_preservados' => 'integer',
            'advertencias_confirmadas' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'sucursal_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function turnos(): HasMany
    {
        return $this->hasMany(Turno::class, 'lote_generacion_id');
    }

    public function turnosCancelados(): HasMany
    {
        return $this->hasMany(Turno::class, 'cancelado_por_lote_id');
    }

    /**
     * @return list<int>
     */
    public function idsCreados(): array
    {
        return Turno::query()
            ->where('lote_generacion_id', (int) $this->id)
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    public function idsCancelados(): array
    {
        return Turno::query()
            ->where('cancelado_por_lote_id', (int) $this->id)
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    public function resultado(bool $reutilizado = true): LoteResultado
    {
        return new LoteResultado(
            loteId: (int) $this->id,
            tipoOrigen: $this->tipo_origen,
            origenId: (int) $this->origen_id,
            accion: $this->accion,
            cantidadCreados: (int) $this->cantidad_creados,
            cantidadCancelados: (int) $this->cantidad_cancelados,
            cantidadPreservados: (int) $this->cantidad_preservados,
            turnoIds: $this->idsCreados(),
            reutilizado: $reutilizado,
        );
    }
}
