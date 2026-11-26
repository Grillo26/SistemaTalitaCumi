<?php

namespace App\Modules\CatalogoServicios\Actions\Servicios;

use App\Modules\CatalogoServicios\Events\ServicioStatusChanged;
use App\Modules\CatalogoServicios\Exceptions\AreaNotFoundException;
use App\Modules\CatalogoServicios\Exceptions\CatalogoConcurrencyException;
use App\Modules\CatalogoServicios\Exceptions\ServicioNotFoundException;
use App\Modules\CatalogoServicios\Models\Area;
use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\CatalogoServicios\Rules\MotivoObligatorioRule;
use App\Modules\CatalogoServicios\Rules\ServicioStateRule;
use App\Modules\CatalogoServicios\Support\ServicioSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Activar o desactivar un servicio (RF-C12, RN-C06, RN-C10).
 *
 * Al activar se exige que el área esté activa (RN-C07).
 */
final class ChangeServicioStatusAction
{
    public function __invoke(
        int $servicioId,
        bool $targetStatus,
        string $motivo,
        int $version,
        ?int $actorId,
        ?string $ip,
    ): Servicio {
        return DB::transaction(function () use (
            $servicioId,
            $targetStatus,
            $motivo,
            $version,
            $actorId,
            $ip,
        ): Servicio {
            $servicio = Servicio::query()->lockForUpdate()->find($servicioId);

            if ($servicio === null) {
                throw new ServicioNotFoundException($servicioId);
            }

            if ((int) $servicio->version !== $version) {
                throw new CatalogoConcurrencyException;
            }

            if ((bool) $servicio->activo === $targetStatus) {
                throw new CatalogoStatusUnchangedException;
            }

            $reason = MotivoObligatorioRule::require($motivo);
            $before = ServicioSnapshot::from($servicio->load(['area', 'tipoServicio']));

            if ($targetStatus) {
                $area = Area::query()->lockForUpdate()->find($servicio->area_id);

                if ($area === null) {
                    throw new AreaNotFoundException($servicio->area_id);
                }

                ServicioStateRule::assertAreaActive((bool) $area->activo, $area->nombre);
            }

            $servicio->activo = $targetStatus;
            $servicio->version = (int) $servicio->version + 1;
            $servicio->save();

            $servicio->load(['area', 'tipoServicio']);
            $after = ServicioSnapshot::from($servicio);

            event(new ServicioStatusChanged(
                servicioId: $servicio->id,
                before: $before,
                after: $after,
                activo: $targetStatus,
                motivo: $reason,
                actorId: $actorId,
                ip: $ip,
            ));

            return $servicio->refresh()->load(['area', 'tipoServicio']);
        });
    }
}
