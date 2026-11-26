<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\ConsumoPaqueteContract;
use App\Modules\InscripcionesAtenciones\Rules\SaldoSesionesRule;

/**
 * RF-009: consumo y saldo de un paquete en endpoint separado.
 */
final class GetConsumoPaqueteQuery
{
    public function __construct(
        private readonly GetPaqueteQuery $detalle,
        private readonly ConsumoPaqueteContract $consumo,
    ) {}

    /**
     * @return array<string, int>
     */
    public function __invoke(int $id, User $actor): array
    {
        $detalle = ($this->detalle)($id, $actor);
        $resumen = $this->consumo->resumen($id);
        $pactadas = (int) $detalle->paquete->cantidad_sesiones;

        return [
            'paquete_id' => $id,
            'pactadas' => $pactadas,
            'realizadas' => $resumen->realizadas,
            'faltadas' => $resumen->faltadas,
            'canceladas' => $resumen->canceladas,
            'saldo' => SaldoSesionesRule::calcular(
                $pactadas,
                $resumen->realizadas,
                $resumen->faltadas,
            ),
        ];
    }
}
