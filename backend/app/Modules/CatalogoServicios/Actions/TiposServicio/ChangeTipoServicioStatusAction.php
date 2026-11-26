<?php

namespace App\Modules\CatalogoServicios\Actions\TiposServicio;

use App\Modules\CatalogoServicios\Events\TipoServicioStatusChanged;
use App\Modules\CatalogoServicios\Exceptions\CatalogoConcurrencyException;
use App\Modules\CatalogoServicios\Exceptions\TipoServicioNotFoundException;
use App\Modules\CatalogoServicios\Models\TipoServicio;
use App\Modules\CatalogoServicios\Rules\MotivoObligatorioRule;
use App\Modules\CatalogoServicios\Rules\TipoServicioStateRule;
use App\Modules\CatalogoServicios\Support\TipoServicioSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Activar o desactivar tipo de servicio (RN-C09, RN-C10).
 */
final class ChangeTipoServicioStatusAction
{
    public function __invoke(
        int $tipoServicioId,
        bool $targetStatus,
        string $motivo,
        int $version,
        ?int $actorId,
        ?string $ip,
    ): TipoServicio {
        return DB::transaction(function () use (
            $tipoServicioId,
            $targetStatus,
            $motivo,
            $version,
            $actorId,
            $ip,
        ): TipoServicio {
            $tipo = TipoServicio::query()->lockForUpdate()->find($tipoServicioId);

            if ($tipo === null) {
                throw new TipoServicioNotFoundException($tipoServicioId);
            }

            if ((int) $tipo->version !== $version) {
                throw new CatalogoConcurrencyException;
            }

            TipoServicioStateRule::assertStatusDifferent((bool) $tipo->activo, $targetStatus);

            $reason = MotivoObligatorioRule::require($motivo);
            $before = TipoServicioSnapshot::from($tipo);

            $tipo->activo = $targetStatus;
            $tipo->version = (int) $tipo->version + 1;
            $tipo->save();

            $after = TipoServicioSnapshot::from($tipo);

            event(new TipoServicioStatusChanged(
                tipoServicioId: $tipo->id,
                before: $before,
                after: $after,
                activo: $targetStatus,
                motivo: $reason,
                actorId: $actorId,
                ip: $ip,
            ));

            return $tipo->refresh();
        });
    }
}
