<?php

namespace App\Modules\CatalogoServicios\Actions\Tarifas;

use App\Models\User;
use App\Modules\CatalogoServicios\Events\TarifaCreated;
use App\Modules\CatalogoServicios\Exceptions\CategoriaTarifaInactivaException;
use App\Modules\CatalogoServicios\Exceptions\CategoriaTarifaNotFoundException;
use App\Modules\CatalogoServicios\Exceptions\ServicioInactivoException;
use App\Modules\CatalogoServicios\Exceptions\ServicioNotFoundException;
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
 * Crear tarifa (RF-T03 a RF-T07, RN-T02, RN-T04).
 */
final class CreateTarifaAction
{
    public function __construct(
        private readonly TarifaAccessScope $scope,
        private readonly TarifaOverlapGuard $overlap,
        private readonly BranchDirectoryContract $branches,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(array $attributes, User $actor, ?string $ip): Tarifa
    {
        return DB::transaction(function () use ($attributes, $actor, $ip): Tarifa {
            $servicioId = (int) $attributes['servicio_id'];
            $categoriaId = (int) $attributes['categoria_tarifa_id'];
            $sucursalId = array_key_exists('sucursal_id', $attributes)
                && $attributes['sucursal_id'] !== null
                ? (int) $attributes['sucursal_id']
                : null;

            $servicio = Servicio::query()->lockForUpdate()->find($servicioId);

            if ($servicio === null) {
                throw new ServicioNotFoundException($servicioId);
            }

            if (! $servicio->activo) {
                throw new ServicioInactivoException($servicioId);
            }

            $categoria = CategoriaTarifa::query()->lockForUpdate()->find($categoriaId);

            if ($categoria === null) {
                throw new CategoriaTarifaNotFoundException($categoriaId);
            }

            if (! $categoria->activo) {
                throw new CategoriaTarifaInactivaException($categoriaId);
            }

            $this->scope->assertCanCreateForBranch($actor, $sucursalId);

            if ($sucursalId !== null) {
                $this->branches->asegurarActiva($sucursalId);
            }

            $monto = TarifaMontoRule::normalizeAndValidate((string) $attributes['monto']);
            $vigenteDesde = (string) $attributes['vigente_desde'];
            $vigenteHasta = array_key_exists('vigente_hasta', $attributes)
                && $attributes['vigente_hasta'] !== null
                ? (string) $attributes['vigente_hasta']
                : null;

            TarifaVigenciaRule::assertValid($vigenteDesde, $vigenteHasta);

            $this->overlap->assertNoOverlap(
                servicioId: $servicioId,
                sucursalId: $sucursalId,
                categoriaTarifaId: $categoriaId,
                vigenteDesde: $vigenteDesde,
                vigenteHasta: $vigenteHasta,
            );

            $tarifa = Tarifa::query()->create([
                'servicio_id' => $servicioId,
                'categoria_tarifa_id' => $categoriaId,
                'sucursal_id' => $sucursalId,
                'monto' => $monto,
                'vigente_desde' => $vigenteDesde,
                'vigente_hasta' => $vigenteHasta,
                'activo' => true,
                'version' => 1,
            ]);

            $tarifa->load(['servicio', 'categoria', 'sucursal']);

            event(new TarifaCreated(
                tarifaId: $tarifa->id,
                snapshot: TarifaSnapshot::from($tarifa),
                actorId: $actor->id,
                ip: $ip,
            ));

            return $tarifa->refresh()->load(['servicio', 'categoria', 'sucursal']);
        });
    }
}
