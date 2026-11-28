<?php

namespace App\Modules\Pagos\Queries;

use App\Models\User;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Pagos\Contracts\PayableEntityContract;
use App\Modules\Pagos\Enums\TipoEntidadPago;
use App\Modules\Pagos\Exceptions\PagoEntidadNoEncontradaException;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Rules\SaldoPagoRule;
use App\Modules\Pagos\Support\PagoPresenter;

/**
 * Historial y saldo de una entidad (08.01/RF-007, CU-03, RN-011, CA-006).
 *
 * Los pagos anulados aparecen marcados y no suman al total pagado.
 */
final class GetHistorialEntidadQuery
{
    public function __construct(
        private readonly PayableEntityContract $entidades,
        private readonly UserBranchAccessContract $access,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(TipoEntidadPago $tipo, int $entidadId, User $actor): array
    {
        $entidad = $this->entidades->resumen($tipo, $entidadId);

        if (! $this->access->puedeAcceder($actor->id, $entidad->sucursalId)) {
            throw new PagoEntidadNoEncontradaException;
        }

        $pagos = Pago::query()
            ->porEntidad($tipo, $entidadId)
            ->orderBy('fecha_pago')
            ->orderBy('id')
            ->get();

        $pagado = '0.00';

        foreach ($pagos as $pago) {
            if ($pago->estado->value === 'vigente') {
                $pagado = bcadd($pagado, (string) $pago->monto, 2);
            }
        }

        $montoEsperado = bcadd($entidad->montoEsperado, '0', 2);

        return [
            'entidad' => $entidad->toArray(),
            'monto_esperado' => $montoEsperado,
            'total_pagado' => $pagado,
            'saldo' => SaldoPagoRule::saldo($montoEsperado, $pagado),
            'pagos' => $pagos
                ->map(static fn (Pago $pago): array => PagoPresenter::fila($pago))
                ->values()
                ->all(),
        ];
    }
}
