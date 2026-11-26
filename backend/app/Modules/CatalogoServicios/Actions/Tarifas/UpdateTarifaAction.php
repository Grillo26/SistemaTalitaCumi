<?php

namespace App\Modules\CatalogoServicios\Actions\Tarifas;

use App\Models\User;
use App\Modules\CatalogoServicios\Events\TarifaUpdated;
use App\Modules\CatalogoServicios\Exceptions\CatalogoConcurrencyException;
use App\Modules\CatalogoServicios\Exceptions\CategoriaTarifaInactivaException;
use App\Modules\CatalogoServicios\Exceptions\CategoriaTarifaNotFoundException;
use App\Modules\CatalogoServicios\Exceptions\ServicioInactivoException;
use App\Modules\CatalogoServicios\Exceptions\ServicioNotFoundException;
use App\Modules\CatalogoServicios\Exceptions\TarifaNotFoundException;
use App\Modules\CatalogoServicios\Models\CategoriaTarifa;
use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\CatalogoServicios\Models\Tarifa;
use App\Modules\CatalogoServicios\Rules\TarifaMontoRule;
use App\Modules\CatalogoServicios\Rules\TarifaVigenciaRule;
use App\Modules\CatalogoServicios\Support\TarifaAccessScope;
use App\Modules\CatalogoServicios\Support\TarifaOverlapGuard;
use App\Modules\CatalogoServicios\Support\TarifaSnapshot;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use Illuminate\Support\Facades\DB;

/**
 * Editar tarifa futura o vigente (RF-T11, RN-T04).
 *
 * `vigente_hasta` no se edita acá: el cierre tiene su propia acción con motivo.
 */
final class UpdateTarifaAction
{
    public function __construct(
        private readonly TarifaAccessScope $scope,
        private readonly TarifaOverlapGuard $overlap,
        private readonly BranchDirectoryContract $branches,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        int $tarifaId,
        array $attributes,
        int $version,
        User $actor,
        ?string $ip,
    ): Tarifa {
        return DB::transaction(function () use (
            $tarifaId,
            $attributes,
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
            TarifaVigenciaRule::assertEditable($tarifa->vigente_hasta?->toDateString());

            $before = TarifaSnapshot::from($tarifa);
            $targetServiceId = $tarifa->servicio_id;
            $targetCategoryId = $tarifa->categoria_tarifa_id;
            $targetSucursalId = $tarifa->sucursal_id;

            if (array_key_exists('servicio_id', $attributes)
                && (int) $attributes['servicio_id'] !== $tarifa->servicio_id) {
                $targetServiceId = (int) $attributes['servicio_id'];
                $servicio = Servicio::query()->lockForUpdate()->find($targetServiceId);

                if ($servicio === null) {
                    throw new ServicioNotFoundException($targetServiceId);
                }

                if (! $servicio->activo) {
                    throw new ServicioInactivoException($targetServiceId);
                }

                $tarifa->servicio_id = $targetServiceId;
            }

            if (array_key_exists('categoria_tarifa_id', $attributes)
                && (int) $attributes['categoria_tarifa_id'] !== $tarifa->categoria_tarifa_id) {
                $targetCategoryId = (int) $attributes['categoria_tarifa_id'];
                $categoria = CategoriaTarifa::query()->lockForUpdate()->find($targetCategoryId);

                if ($categoria === null) {
                    throw new CategoriaTarifaNotFoundException($targetCategoryId);
                }

                if (! $categoria->activo) {
                    throw new CategoriaTarifaInactivaException($targetCategoryId);
                }

                $tarifa->categoria_tarifa_id = $targetCategoryId;
            }

            if (array_key_exists('sucursal_id', $attributes)) {
                $targetSucursalId = $attributes['sucursal_id'] !== null
                    ? (int) $attributes['sucursal_id']
                    : null;

                if ($targetSucursalId !== $tarifa->sucursal_id) {
                    $this->scope->assertCanCreateForBranch($actor, $targetSucursalId);

                    if ($targetSucursalId !== null) {
                        $this->branches->asegurarActiva($targetSucursalId);
                    }

                    $tarifa->sucursal_id = $targetSucursalId;
                }
            }

            $vigenteDesde = array_key_exists('vigente_desde', $attributes)
                ? (string) $attributes['vigente_desde']
                : $tarifa->vigente_desde->toDateString();
            $vigenteHasta = $tarifa->vigente_hasta?->toDateString();

            TarifaVigenciaRule::assertValid($vigenteDesde, $vigenteHasta);

            if (array_key_exists('monto', $attributes)) {
                $tarifa->monto = TarifaMontoRule::normalizeAndValidate((string) $attributes['monto']);
            }

            if (array_key_exists('vigente_desde', $attributes)) {
                $tarifa->vigente_desde = $vigenteDesde;
            }

            $this->overlap->assertNoOverlap(
                servicioId: $targetServiceId,
                sucursalId: $targetSucursalId,
                categoriaTarifaId: $targetCategoryId,
                vigenteDesde: $vigenteDesde,
                vigenteHasta: $vigenteHasta,
                ignoreId: $tarifa->id,
            );

            $tarifa->version = (int) $tarifa->version + 1;
            $tarifa->save();
            $tarifa->load(['servicio', 'categoria', 'sucursal']);

            event(new TarifaUpdated(
                tarifaId: $tarifa->id,
                before: $before,
                after: TarifaSnapshot::from($tarifa),
                actorId: $actor->id,
                ip: $ip,
            ));

            return $tarifa->refresh()->load(['servicio', 'categoria', 'sucursal']);
        });
    }
}
