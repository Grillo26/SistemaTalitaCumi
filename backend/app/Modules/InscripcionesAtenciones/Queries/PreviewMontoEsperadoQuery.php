<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\CatalogoServicios\Contracts\TarifaAplicableResolverContract;
use App\Modules\CatalogoServicios\Contracts\TarifaAplicableResumen;
use App\Modules\InscripcionesAtenciones\Support\SalaGrupalServicioGuard;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;

/**
 * RF-005: monto esperado antes de guardar.
 *
 * El monto siempre lo calcula el backend a partir de la tarifa aplicable.
 */
final class PreviewMontoEsperadoQuery
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly BranchDirectoryContract $branches,
        private readonly TarifaAplicableResolverContract $tarifas,
        private readonly SalaGrupalServicioGuard $servicios,
    ) {}

    public function __invoke(
        int $servicioId,
        int $sucursalId,
        int $categoriaTarifaId,
        string $fechaInicio,
        User $actor,
    ): ?TarifaAplicableResumen {
        if (! $this->access->puedeAcceder($actor->id, $sucursalId)) {
            throw new BranchAccessDeniedException;
        }

        $this->branches->asegurarActiva($sucursalId);
        $this->servicios->assertServicioValido($servicioId);

        return $this->tarifas->resolver(
            servicioId: $servicioId,
            sucursalId: $sucursalId,
            categoriaTarifaId: $categoriaTarifaId,
            fecha: $fechaInicio,
        );
    }
}
