<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Events\SalaUpdated;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Organizacion\Exceptions\EspacioNotFoundException;
use App\Modules\Organizacion\Exceptions\SalaNotFoundException;
use App\Modules\Organizacion\Exceptions\SpaceConcurrencyException;
use App\Modules\Organizacion\Exceptions\TipoSalaNotFoundException;
use App\Modules\Organizacion\Exceptions\TurnoCatalogoNotFoundException;
use App\Modules\Organizacion\Models\Area;
use App\Modules\Organizacion\Models\Sala;
use App\Modules\Organizacion\Models\TipoSala;
use App\Modules\Organizacion\Models\TurnoCatalogo;
use App\Modules\Organizacion\Rules\CatalogAvailabilityRule;
use App\Modules\Organizacion\Rules\SpaceNameNormalizationRule;
use App\Modules\Organizacion\Support\SpaceSnapshot;
use App\Modules\Organizacion\Support\SpaceUniquenessGuard;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Editar sala grupal (RF-010 / RN-001, RN-002, RN-004).
 */
final class UpdateSalaAction
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly BranchDirectoryContract $branches,
        private readonly SpaceUniquenessGuard $uniqueness,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        int $id,
        array $attributes,
        int $expectedVersion,
        ?int $actorId,
        bool $hasGlobalScope,
    ): Sala {
        return DB::transaction(function () use ($id, $attributes, $expectedVersion, $actorId, $hasGlobalScope): Sala {
            $sala = Sala::query()->whereKey($id)->lockForUpdate()->first();

            if ($sala === null) {
                throw new SalaNotFoundException($id);
            }

            if (! $hasGlobalScope
                && ($actorId === null || ! $this->access->puedeAcceder($actorId, $sala->sucursal_id))) {
                throw new BranchAccessDeniedException;
            }

            if ($sala->version !== $expectedVersion) {
                throw new SpaceConcurrencyException('sala');
            }

            $changes = $this->resolveChanges($sala, $attributes, $hasGlobalScope, $actorId);

            if ($changes === []) {
                return $sala->load(['sucursal', 'area', 'tipoSala', 'turnoCatalogo']);
            }

            $before = SpaceSnapshot::fromSala($sala);

            $sala->fill($changes);
            $sala->version = $sala->version + 1;

            try {
                $sala->save();
            } catch (QueryException $exception) {
                $this->uniqueness->translateUniqueViolation(
                    $exception,
                    (int) ($changes['sucursal_id'] ?? $sala->sucursal_id),
                    (int) ($changes['tipo_sala_id'] ?? $sala->tipo_sala_id),
                    (int) ($changes['turno_catalogo_id'] ?? $sala->turno_catalogo_id),
                );

                throw $exception;
            }

            $sala->refresh();

            event(new SalaUpdated(
                salaId: $sala->id,
                before: $before,
                after: SpaceSnapshot::fromSala($sala),
                changedFields: array_keys($changes),
                actorId: $actorId,
            ));

            return $sala->fresh(['sucursal', 'area', 'tipoSala', 'turnoCatalogo']);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function resolveChanges(
        Sala $sala,
        array $attributes,
        bool $hasGlobalScope,
        ?int $actorId,
    ): array {
        $changes = [];
        $combinationChanged = false;

        if (array_key_exists('sucursal_id', $attributes)) {
            $sucursalId = (int) $attributes['sucursal_id'];

            if ($sucursalId !== $sala->sucursal_id) {
                if (! $hasGlobalScope
                    && ($actorId === null || ! $this->access->puedeAcceder($actorId, $sucursalId))) {
                    throw new BranchAccessDeniedException;
                }

                $this->branches->asegurarActiva($sucursalId);
                $changes['sucursal_id'] = $sucursalId;
                $combinationChanged = true;
            }
        }

        if (array_key_exists('tipo_sala_id', $attributes)) {
            $tipoSalaId = (int) $attributes['tipo_sala_id'];

            if ($tipoSalaId !== $sala->tipo_sala_id) {
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

                $changes['tipo_sala_id'] = $tipoSalaId;
                $combinationChanged = true;
            }
        }

        if (array_key_exists('turno_catalogo_id', $attributes)) {
            $turnoId = (int) $attributes['turno_catalogo_id'];

            if ($turnoId !== $sala->turno_catalogo_id) {
                $turno = TurnoCatalogo::query()->whereKey($turnoId)->lockForUpdate()->first();

                if ($turno === null) {
                    throw new TurnoCatalogoNotFoundException($turnoId);
                }

                CatalogAvailabilityRule::assertActive(
                    $turno->activo,
                    'turno',
                    'turno_catalogo_id',
                    $turno->nombre,
                );

                $changes['turno_catalogo_id'] = $turnoId;
                $combinationChanged = true;
            }
        }

        if (array_key_exists('area_id', $attributes)) {
            $areaId = (int) $attributes['area_id'];

            if ($areaId !== $sala->area_id) {
                $area = Area::query()->find($areaId);

                if ($area === null) {
                    throw new EspacioNotFoundException('área', $areaId);
                }

                CatalogAvailabilityRule::assertActive($area->activo, 'área', 'area_id', $area->nombre);
                $changes['area_id'] = $areaId;
            }
        }

        if ($combinationChanged) {
            $this->uniqueness->assertSalaCombinationAvailable(
                (int) ($changes['sucursal_id'] ?? $sala->sucursal_id),
                (int) ($changes['tipo_sala_id'] ?? $sala->tipo_sala_id),
                (int) ($changes['turno_catalogo_id'] ?? $sala->turno_catalogo_id),
                $sala->id,
            );
        }

        if (array_key_exists('nombre', $attributes)) {
            $nombre = SpaceNameNormalizationRule::normalize((string) $attributes['nombre']);

            if ($nombre !== $sala->nombre) {
                $changes['nombre'] = $nombre;
            }
        }

        if (array_key_exists('observaciones', $attributes)) {
            $value = $attributes['observaciones'] === null
                ? null
                : trim((string) $attributes['observaciones']);

            if ($value !== $sala->observaciones) {
                $changes['observaciones'] = $value;
            }
        }

        return $changes;
    }
}
