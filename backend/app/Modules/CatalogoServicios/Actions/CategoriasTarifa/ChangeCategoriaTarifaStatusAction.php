<?php

namespace App\Modules\CatalogoServicios\Actions\CategoriasTarifa;

use App\Modules\CatalogoServicios\Events\CategoriaTarifaStatusChanged;
use App\Modules\CatalogoServicios\Exceptions\CatalogoConcurrencyException;
use App\Modules\CatalogoServicios\Exceptions\CatalogoStatusUnchangedException;
use App\Modules\CatalogoServicios\Exceptions\CategoriaTarifaNotFoundException;
use App\Modules\CatalogoServicios\Models\CategoriaTarifa;
use App\Modules\CatalogoServicios\Models\Tarifa;
use App\Modules\CatalogoServicios\Rules\CategoriaTarifaStateRule;
use App\Modules\CatalogoServicios\Rules\MotivoObligatorioRule;
use App\Modules\CatalogoServicios\Support\CategoriaTarifaSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Activar o desactivar categoría (RF-T02 / RN-T12).
 *
 * El motivo es obligatorio solo al desactivar.
 */
final class ChangeCategoriaTarifaStatusAction
{
    public function __invoke(
        int $categoriaTarifaId,
        bool $targetStatus,
        ?string $motivo,
        int $version,
        ?int $actorId,
        ?string $ip,
    ): CategoriaTarifa {
        return DB::transaction(function () use (
            $categoriaTarifaId,
            $targetStatus,
            $motivo,
            $version,
            $actorId,
            $ip,
        ): CategoriaTarifa {
            $categoria = CategoriaTarifa::query()->lockForUpdate()->find($categoriaTarifaId);

            if ($categoria === null) {
                throw new CategoriaTarifaNotFoundException($categoriaTarifaId);
            }

            if ((int) $categoria->version !== $version) {
                throw new CatalogoConcurrencyException;
            }

            if ((bool) $categoria->activo === $targetStatus) {
                throw new CatalogoStatusUnchangedException;
            }

            $reason = $targetStatus ? null : MotivoObligatorioRule::require($motivo);
            $before = CategoriaTarifaSnapshot::from($categoria);

            if (! $targetStatus) {
                $activeTariffs = Tarifa::query()
                    ->where('categoria_tarifa_id', $categoria->id)
                    ->vigentes()
                    ->count();

                CategoriaTarifaStateRule::assertCanDeactivate(
                    $activeTariffs > 0,
                    $categoria->nombre,
                    $activeTariffs,
                );
            }

            $categoria->activo = $targetStatus;
            $categoria->version = (int) $categoria->version + 1;
            $categoria->save();

            event(new CategoriaTarifaStatusChanged(
                categoriaTarifaId: $categoria->id,
                before: $before,
                after: CategoriaTarifaSnapshot::from($categoria),
                activo: $targetStatus,
                motivo: $reason,
                actorId: $actorId,
                ip: $ip,
            ));

            return $categoria->refresh();
        });
    }
}
