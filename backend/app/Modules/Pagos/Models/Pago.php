<?php

namespace App\Modules\Pagos\Models;

use App\Models\User;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Models\Tutor;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\TipoEntidadPago;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Pago (módulo 08, submódulo 01).
 *
 * Documento inmutable (RN-006, RNF-005): no se edita ni se borra. El único
 * cambio permitido es `vigente → anulado` con sus campos de anulación, que
 * escribirá 8.4. El trigger `tg_pago_inmutable` aplica la misma regla en
 * PostgreSQL.
 */
class Pago extends Model
{
    public const CAMPOS_ANULACION = ['estado', 'anulado_en', 'anulado_por', 'motivo_anulacion', 'updated_at'];

    protected $table = 'pago';

    protected $fillable = [
        'numero_correlativo',
        'sucursal_id',
        'sucursal_codigo_snapshot',
        'sucursal_nombre_snapshot',
        'paciente_id',
        'paciente_nombre_snapshot',
        'tutor_id',
        'tutor_nombre_snapshot',
        'inscripcion_sala_id',
        'paquete_sesiones_id',
        'atencion_entrevista_id',
        'atencion_diagnostico_id',
        'entidad_referencia_snapshot',
        'monto_esperado_snapshot',
        'monto',
        'saldo_resultante',
        'medio_pago',
        'referencia_pago',
        'grupo_pago_id',
        'fecha_pago',
        'fecha_futura_autorizada',
        'motivo_fecha_futura',
        'autorizado_por_id',
        'estado',
        'usuario_registro_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sucursal_id' => 'integer',
            'paciente_id' => 'integer',
            'tutor_id' => 'integer',
            'inscripcion_sala_id' => 'integer',
            'paquete_sesiones_id' => 'integer',
            'atencion_entrevista_id' => 'integer',
            'atencion_diagnostico_id' => 'integer',
            'monto_esperado_snapshot' => 'decimal:2',
            'monto' => 'decimal:2',
            'saldo_resultante' => 'decimal:2',
            'fecha_pago' => 'date:Y-m-d',
            'fecha_futura_autorizada' => 'boolean',
            'estado' => EstadoPago::class,
            'anulado_en' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Pago $pago): void {
            $noPermitidos = array_diff(array_keys($pago->getDirty()), self::CAMPOS_ANULACION);

            if ($noPermitidos !== []) {
                throw new LogicException('Los pagos son inmutables: solo pueden anularse con motivo (RN-006).');
            }

            $estadoOriginal = $pago->getOriginal('estado');
            $estadoOriginal = $estadoOriginal instanceof EstadoPago ? $estadoOriginal->value : $estadoOriginal;

            if ($estadoOriginal !== EstadoPago::VIGENTE->value) {
                throw new LogicException('Solo un pago vigente puede anularse (RN-008).');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('Los pagos no se eliminan: se anulan con motivo (RN-006).');
        });
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'sucursal_id');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Tutor::class, 'tutor_id');
    }

    public function usuarioRegistro(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_registro_id');
    }

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where('pago.estado', EstadoPago::VIGENTE->value);
    }

    public function scopePorSucursales(Builder $query, array $sucursalIds): Builder
    {
        return $query->whereIn('pago.sucursal_id', $sucursalIds);
    }

    public function scopePorEntidad(Builder $query, TipoEntidadPago $tipo, int $entidadId): Builder
    {
        $columna = $tipo->columna();

        if ($columna === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('pago.'.$columna, $entidadId);
    }
}
