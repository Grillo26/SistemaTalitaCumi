<?php

namespace App\Modules\CatalogoServicios\Contracts;

use App\Modules\CatalogoServicios\Queries\ResolveTarifaAplicableQuery;

/**
 * Implementación real con caché e invalidación por eventos.
 */
final class EloquentTarifaAplicableResolver implements TarifaAplicableResolverContract
{
    public function __construct(private readonly ResolveTarifaAplicableQuery $query) {}

    public function resolver(
        int $servicioId,
        int $sucursalId,
        int $categoriaTarifaId,
        string $fecha,
    ): ?TarifaAplicableResumen {
        return ($this->query)($servicioId, $sucursalId, $categoriaTarifaId, $fecha);
    }
}
