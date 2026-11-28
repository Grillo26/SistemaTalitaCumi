<?php

namespace App\Modules\Pagos\Support;

use App\Modules\Pagos\Models\Pago;

/**
 * Snapshot en lista blanca para bitácora y eventos (sin datos clínicos).
 */
final class PagoSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(Pago $pago): array
    {
        return [
            'id' => (int) $pago->id,
            'numero_correlativo' => $pago->numero_correlativo,
            'numero_comprobante' => self::numeroComprobante($pago),
            'sucursal_id' => (int) $pago->sucursal_id,
            'paciente_id' => (int) $pago->paciente_id,
            'tutor_id' => (int) $pago->tutor_id,
            'entidad_referencia' => $pago->entidad_referencia_snapshot,
            'monto_esperado' => (string) $pago->monto_esperado_snapshot,
            'monto' => (string) $pago->monto,
            'saldo_resultante' => (string) $pago->saldo_resultante,
            'medio_pago' => $pago->medio_pago,
            'referencia_pago' => $pago->referencia_pago,
            'grupo_pago_id' => $pago->grupo_pago_id,
            'fecha_pago' => $pago->fecha_pago->toDateString(),
            'fecha_futura_autorizada' => (bool) $pago->fecha_futura_autorizada,
            'estado' => $pago->estado->value,
        ];
    }

    public static function numeroComprobante(Pago $pago): string
    {
        return $pago->sucursal_codigo_snapshot.'-'.$pago->numero_correlativo;
    }
}
