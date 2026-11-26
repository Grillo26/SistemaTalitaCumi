<?php

namespace App\Modules\CatalogoServicios\Contracts;

/**
 * Resolución de tarifa aplicable para inscripciones/pagos (RF-T08 / RF-T16).
 *
 * El consumidor (módulo 06) debe validar que la sucursal esté en su alcance.
 */
interface TarifaAplicableResolverContract
{
    /**
     * @throws \App\Modules\CatalogoServicios\Exceptions\ServicioNotFoundException
     * @throws \App\Modules\CatalogoServicios\Exceptions\ServicioInactivoException
     * @throws \App\Modules\CatalogoServicios\Exceptions\CategoriaTarifaNotFoundException
     * @throws \App\Modules\CatalogoServicios\Exceptions\CategoriaTarifaInactivaException
     * @throws \App\Modules\Organizacion\Exceptions\BranchNotFoundException
     * @throws \App\Modules\Organizacion\Exceptions\InactiveBranchException
     */
    public function resolver(
        int $servicioId,
        int $sucursalId,
        int $categoriaTarifaId,
        string $fecha,
    ): ?TarifaAplicableResumen;
}
