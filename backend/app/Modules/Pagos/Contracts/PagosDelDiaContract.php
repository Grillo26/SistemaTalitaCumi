<?php

namespace App\Modules\Pagos\Contracts;

/**
 * Pagos vigentes de un día y sucursal, separados por medio (08.01/RF-012).
 *
 * Consumidor previsto: Caja diaria y cierre (08.03). Solo cuenta pagos
 * `vigente` (RN-011).
 */
interface PagosDelDiaContract
{
    public function resumen(int $sucursalId, string $fecha): PagosDelDiaResumen;
}
