<?php

namespace App\Modules\Pagos\Contracts;

use App\Modules\Pagos\Models\MedioPago;
use App\Modules\Pagos\Models\Pago;

/**
 * Implementación real para Caja (08.03): solo pagos vigentes, separados por medio (RF-012).
 */
final class EloquentPagosDelDia implements PagosDelDiaContract
{
    public function resumen(int $sucursalId, string $fecha): PagosDelDiaResumen
    {
        $porMedio = MedioPago::query()
            ->activos()
            ->pluck('codigo')
            ->mapWithKeys(static fn (string $codigo): array => [$codigo => ['cantidad' => 0, 'total' => '0.00']])
            ->all();

        $pagos = Pago::query()
            ->vigentes()
            ->where('pago.sucursal_id', $sucursalId)
            ->whereDate('pago.fecha_pago', $fecha)
            ->get(['medio_pago', 'monto']);

        $total = '0.00';

        foreach ($pagos as $pago) {
            $codigo = $pago->medio_pago;
            $actual = $porMedio[$codigo] ?? ['cantidad' => 0, 'total' => '0.00'];
            $porMedio[$codigo] = [
                'cantidad' => $actual['cantidad'] + 1,
                'total' => bcadd($actual['total'], (string) $pago->monto, 2),
            ];
            $total = bcadd($total, (string) $pago->monto, 2);
        }

        return new PagosDelDiaResumen(
            sucursalId: $sucursalId,
            fecha: $fecha,
            porMedio: $porMedio,
            total: $total,
            cantidad: $pagos->count(),
        );
    }
}
