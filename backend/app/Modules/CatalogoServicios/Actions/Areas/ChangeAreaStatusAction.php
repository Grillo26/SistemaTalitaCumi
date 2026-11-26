<?php

namespace App\Modules\CatalogoServicios\Actions\Areas;

use App\Modules\CatalogoServicios\Events\AreaStatusChanged;
use App\Modules\CatalogoServicios\Exceptions\AreaNotFoundException;
use App\Modules\CatalogoServicios\Exceptions\CatalogoConcurrencyException;
use App\Modules\CatalogoServicios\Exceptions\CatalogoStatusUnchangedException;
use App\Modules\CatalogoServicios\Models\Area;
use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\CatalogoServicios\Rules\AreaStateRule;
use App\Modules\CatalogoServicios\Rules\MotivoObligatorioRule;
use App\Modules\CatalogoServicios\Support\AreaSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Activar o desactivar un área (RF-C02, RN-C07, RN-C10).
 *
 * Siempre exige motivo, incluso al activar.
 */
final class ChangeAreaStatusAction
{
    public function __invoke(
        int $areaId,
        bool $targetStatus,
        string $motivo,
        int $version,
        ?int $actorId,
        ?string $ip,
    ): Area {
        return DB::transaction(function () use (
            $areaId,
            $targetStatus,
            $motivo,
            $version,
            $actorId,
            $ip,
        ): Area {
            $area = Area::query()->lockForUpdate()->find($areaId);

            if ($area === null) {
                throw new AreaNotFoundException($areaId);
            }

            if ((int) $area->version !== $version) {
                throw new CatalogoConcurrencyException;
            }

            if ((bool) $area->activo === $targetStatus) {
                throw new CatalogoStatusUnchangedException;
            }

            $reason = MotivoObligatorioRule::require($motivo);
            $before = AreaSnapshot::from($area);

            if (! $targetStatus) {
                // El área ya está bloqueada con lockForUpdate; alcanza para
                // serializar la desactivación contra la activación de servicios.
                $activeServices = Servicio::query()
                    ->where('area_id', $area->id)
                    ->where('activo', true)
                    ->count();

                AreaStateRule::assertCanDeactivate(
                    $activeServices > 0,
                    $area->nombre,
                    $activeServices,
                );
            }

            $area->activo = $targetStatus;
            $area->version = (int) $area->version + 1;
            $area->save();

            $after = AreaSnapshot::from($area);

            event(new AreaStatusChanged(
                areaId: $area->id,
                before: $before,
                after: $after,
                activo: $targetStatus,
                motivo: $reason,
                actorId: $actorId,
                ip: $ip,
            ));

            return $area->refresh();
        });
    }
}
