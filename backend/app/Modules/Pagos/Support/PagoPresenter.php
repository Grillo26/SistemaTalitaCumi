<?php

namespace App\Modules\Pagos\Support;

use App\Modules\Pagos\Enums\TipoEntidadPago;
use App\Modules\Pagos\Models\Pago;

/**
 * Presentación de un pago para la API. Los montos viajan como string decimal.
 */
final class PagoPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function fila(Pago $pago): array
    {
        return [
            'id' => (int) $pago->id,
            'numero_correlativo' => $pago->numero_correlativo,
            'numero_comprobante' => PagoSnapshot::numeroComprobante($pago),
            'sucursal_id' => (int) $pago->sucursal_id,
            'sucursal_codigo' => $pago->sucursal_codigo_snapshot,
            'sucursal_nombre' => $pago->sucursal_nombre_snapshot,
            'paciente_id' => (int) $pago->paciente_id,
            'paciente_nombre' => $pago->paciente_nombre_snapshot,
            'tutor_id' => (int) $pago->tutor_id,
            'tutor_nombre' => $pago->tutor_nombre_snapshot,
            'entidad' => self::entidad($pago),
            'entidad_referencia' => $pago->entidad_referencia_snapshot,
            'monto_esperado' => (string) $pago->monto_esperado_snapshot,
            'monto' => (string) $pago->monto,
            'saldo_resultante' => (string) $pago->saldo_resultante,
            'medio_pago' => $pago->medio_pago,
            'referencia_pago' => $pago->referencia_pago,
            'grupo_pago_id' => $pago->grupo_pago_id,
            'fecha_pago' => $pago->fecha_pago->toDateString(),
            'fecha_futura_autorizada' => (bool) $pago->fecha_futura_autorizada,
            'motivo_fecha_futura' => $pago->motivo_fecha_futura,
            'estado' => $pago->estado->value,
            'estado_label' => $pago->estado->label(),
            'anulado_en' => $pago->anulado_en?->toIso8601String(),
            'motivo_anulacion' => $pago->motivo_anulacion,
            'usuario_registro_id' => (int) $pago->usuario_registro_id,
            'created_at' => $pago->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{tipo: string|null, id: int|null}
     */
    public static function entidad(Pago $pago): array
    {
        foreach (TipoEntidadPago::cases() as $tipo) {
            $columna = $tipo->columna();

            if ($columna !== null && $pago->{$columna} !== null) {
                return ['tipo' => $tipo->value, 'id' => (int) $pago->{$columna}];
            }
        }

        return ['tipo' => null, 'id' => null];
    }
}
