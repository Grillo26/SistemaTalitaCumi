<?php

namespace App\Modules\CatalogoServicios\Actions\Tarifas;

use App\Models\User;
use App\Modules\CatalogoServicios\Events\TarifaVigenciaClosed;
use App\Modules\CatalogoServicios\Exceptions\CatalogoConcurrencyException;
use App\Modules\CatalogoServicios\Exceptions\TarifaNotFoundException;
use App\Modules\CatalogoServicios\Models\Tarifa;
use App\Modules\CatalogoServicios\Rules\MotivoObligatorioRule;
use App\Modules\CatalogoServicios\Rules\TarifaVigenciaRule;
use App\Modules\CatalogoServicios\Support\TarifaAccessScope;
use App\Modules\CatalogoServicios\Support\TarifaSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Cerrar vigencia de tarifa (RF-T12, RN-T03, RN-T12).
 */
final class CloseTarifaVigenciaAction
{
    public function __construct(private readonly TarifaAccessScope $scope) {}

    public function __invoke(
        int $tarifaId,
        string $vigenteHasta,
        string $motivo,
        int $version,
        User $actor,
        ?string $ip,
    ): Tarifa {
        return DB::transaction(function () use (
            $tarifaId,
            $vigenteHasta,
            $motivo,
            $version,
            $actor,
            $ip,
        ): Tarifa {
            $tarifa = Tarifa::query()->lockForUpdate()->find($tarifaId);

            if ($tarifa === null) {
                throw new TarifaNotFoundException($tarifaId);
            }

            if ((int) $tarifa->version !== $version) {
                throw new CatalogoConcurrencyException;
            }

            $this->scope->assertCanManage($actor, $tarifa);

            $reason = MotivoObligatorioRule::require($motivo);

            TarifaVigenciaRule::assertCanClose(
                $tarifa->vigente_hasta?->toDateString(),
                $vigenteHasta,
                $tarifa->vigente_desde->toDateString(),
            );

            $before = TarifaSnapshot::from($tarifa);
            $tarifa->vigente_hasta = $vigenteHasta;
            $tarifa->version = (int) $tarifa->version + 1;
            $tarifa->save();
            $tarifa->load(['servicio', 'categoria', 'sucursal']);

            event(new TarifaVigenciaClosed(
                tarifaId: $tarifa->id,
                before: $before,
                after: TarifaSnapshot::from($tarifa),
                motivo: $reason,
                actorId: $actor->id,
                ip: $ip,
            ));

            return $tarifa->refresh()->load(['servicio', 'categoria', 'sucursal']);
        });
    }
}
