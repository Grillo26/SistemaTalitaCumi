<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\CatalogoServicios\Contracts\TarifaAplicableResolverContract;
use App\Modules\CatalogoServicios\Contracts\TarifaAplicableResumen;
use App\Modules\InscripcionesAtenciones\Support\DiagnosticoCatalogoGuard;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;

/**
 * Monto esperado de un diagnóstico antes de guardar (RF-006).
 */
final class PreviewMontoDiagnosticoQuery
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly BranchDirectoryContract $branches,
        private readonly TarifaAplicableResolverContract $tarifas,
        private readonly DiagnosticoCatalogoGuard $catalogo,
    ) {}

    public function __invoke(
        int $tipoDiagnosticoId,
        int $sucursalId,
        int $categoriaTarifaId,
        string $fechaInicio,
        User $actor,
    ): ?TarifaAplicableResumen {
        if (! $this->access->puedeAcceder($actor->id, $sucursalId)) {
            throw new BranchAccessDeniedException;
        }

        $this->branches->asegurarActiva($sucursalId);
        $tipo = $this->catalogo->resumen($tipoDiagnosticoId);

        return $this->tarifas->resolver(
            servicioId: (int) $tipo['servicio_id'],
            sucursalId: $sucursalId,
            categoriaTarifaId: $categoriaTarifaId,
            fecha: $fechaInicio,
        );
    }
}
