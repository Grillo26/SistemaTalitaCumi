<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Events\SalaCreated;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Organizacion\Exceptions\EspacioNotFoundException;
use App\Modules\Organizacion\Exceptions\TipoSalaNotFoundException;
use App\Modules\Organizacion\Exceptions\TurnoCatalogoNotFoundException;
use App\Modules\Organizacion\Models\Area;
use App\Modules\Organizacion\Models\Sala;
use App\Modules\Organizacion\Models\TipoSala;
use App\Modules\Organizacion\Models\TurnoCatalogo;
use App\Modules\Organizacion\Rules\CatalogAvailabilityRule;
use App\Modules\Organizacion\Rules\SpaceInitialStatusRule;
use App\Modules\Organizacion\Rules\SpaceNameNormalizationRule;
use App\Modules\Organizacion\Support\SpaceSnapshot;
use App\Modules\Organizacion\Support\SpaceUniquenessGuard;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Crear sala grupal (RF-004/RF-005 / RN-001, RN-002, RN-004).
 */
final class CreateSalaAction
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly BranchDirectoryContract $branches,
        private readonly SpaceUniquenessGuard $uniqueness,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(array $attributes, ?int $actorId, bool $hasGlobalScope): Sala
    {
        $sucursalId = (int) $attributes['sucursal_id'];
        $tipoSalaId = (int) $attributes['tipo_sala_id'];
        $turnoCatalogoId = (int) $attributes['turno_catalogo_id'];
        $areaId = (int) $attributes['area_id'];
        $nombre = SpaceNameNormalizationRule::normalize((string) $attributes['nombre']);
        $observaciones = $attributes['observaciones'] ?? null;
        $activo = SpaceInitialStatusRule::resolve($attributes['activo'] ?? null);

        if (! $hasGlobalScope && ($actorId === null || ! $this->access->puedeAcceder($actorId, $sucursalId))) {
            throw new BranchAccessDeniedException;
        }

        return DB::transaction(function () use (
            $sucursalId,
            $tipoSalaId,
            $turnoCatalogoId,
            $areaId,
            $nombre,
            $observaciones,
            $activo,
            $actorId,
        ): Sala {
            $this->branches->asegurarActiva($sucursalId);

            $tipoSala = TipoSala::query()->whereKey($tipoSalaId)->lockForUpdate()->first();

            if ($tipoSala === null) {
                throw new TipoSalaNotFoundException($tipoSalaId);
            }

            CatalogAvailabilityRule::assertActive(
                $tipoSala->activo,
                'tipo de sala',
                'tipo_sala_id',
                $tipoSala->nombre,
            );

            $turno = TurnoCatalogo::query()->whereKey($turnoCatalogoId)->lockForUpdate()->first();

            if ($turno === null) {
                throw new TurnoCatalogoNotFoundException($turnoCatalogoId);
            }

            CatalogAvailabilityRule::assertActive(
                $turno->activo,
                'turno',
                'turno_catalogo_id',
                $turno->nombre,
            );

            $area = Area::query()->find($areaId);

            if ($area === null) {
                throw new EspacioNotFoundException('área', $areaId);
            }

            CatalogAvailabilityRule::assertActive($area->activo, 'área', 'area_id', $area->nombre);

            $this->uniqueness->assertSalaCombinationAvailable($sucursalId, $tipoSalaId, $turnoCatalogoId);

            try {
                $sala = Sala::query()->create([
                    'sucursal_id' => $sucursalId,
                    'area_id' => $areaId,
                    'tipo_sala_id' => $tipoSalaId,
                    'turno_catalogo_id' => $turnoCatalogoId,
                    'nombre' => $nombre,
                    'observaciones' => $observaciones === null ? null : trim((string) $observaciones),
                    'activo' => $activo,
                    'version' => 1,
                ]);
            } catch (QueryException $exception) {
                $this->uniqueness->translateUniqueViolation(
                    $exception,
                    $sucursalId,
                    $tipoSalaId,
                    $turnoCatalogoId,
                );

                throw $exception;
            }

            $sala->load(['sucursal', 'area', 'tipoSala', 'turnoCatalogo']);

            event(new SalaCreated(
                salaId: $sala->id,
                snapshot: SpaceSnapshot::fromSala($sala),
                actorId: $actorId,
            ));

            return $sala->fresh(['sucursal', 'area', 'tipoSala', 'turnoCatalogo']);
        });
    }
}
