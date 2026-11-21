<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Events\AmbienteUpdated;
use App\Modules\Organizacion\Exceptions\AmbienteNotFoundException;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Organizacion\Exceptions\SpaceConcurrencyException;
use App\Modules\Organizacion\Models\Ambiente;
use App\Modules\Organizacion\Rules\SpaceNameNormalizationRule;
use App\Modules\Organizacion\Support\SpaceSnapshot;
use App\Modules\Organizacion\Support\SpaceUniquenessGuard;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Editar ambiente (RF-010 / RN-004).
 */
final class UpdateAmbienteAction
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
    ): Ambiente {
        return DB::transaction(function () use ($id, $attributes, $expectedVersion, $actorId, $hasGlobalScope): Ambiente {
            $ambiente = Ambiente::query()->whereKey($id)->lockForUpdate()->first();

            if ($ambiente === null) {
                throw new AmbienteNotFoundException($id);
            }

            if (! $hasGlobalScope
                && ($actorId === null || ! $this->access->puedeAcceder($actorId, $ambiente->sucursal_id))) {
                throw new BranchAccessDeniedException;
            }

            if ($ambiente->version !== $expectedVersion) {
                throw new SpaceConcurrencyException('ambiente');
            }

            $changes = $this->resolveChanges($ambiente, $attributes, $hasGlobalScope, $actorId);

            if ($changes === []) {
                return $ambiente->load('sucursal');
            }

            $before = SpaceSnapshot::fromAmbiente($ambiente);

            $ambiente->fill($changes);
            $ambiente->version = $ambiente->version + 1;

            try {
                $ambiente->save();
            } catch (QueryException $exception) {
                $this->uniqueness->translateUniqueViolation($exception);

                throw $exception;
            }

            $ambiente->refresh();

            event(new AmbienteUpdated(
                ambienteId: $ambiente->id,
                before: $before,
                after: SpaceSnapshot::fromAmbiente($ambiente),
                changedFields: array_keys($changes),
                actorId: $actorId,
            ));

            return $ambiente->fresh('sucursal');
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function resolveChanges(
        Ambiente $ambiente,
        array $attributes,
        bool $hasGlobalScope,
        ?int $actorId,
    ): array {
        $changes = [];
        $nameOrBranchChanged = false;

        if (array_key_exists('sucursal_id', $attributes)) {
            $sucursalId = (int) $attributes['sucursal_id'];

            if ($sucursalId !== $ambiente->sucursal_id) {
                if (! $hasGlobalScope
                    && ($actorId === null || ! $this->access->puedeAcceder($actorId, $sucursalId))) {
                    throw new BranchAccessDeniedException;
                }

                $this->branches->asegurarActiva($sucursalId);
                $changes['sucursal_id'] = $sucursalId;
                $nameOrBranchChanged = true;
            }
        }

        if (array_key_exists('uso', $attributes)) {
            $uso = (string) $attributes['uso'];

            if ($uso !== $ambiente->uso->value) {
                $changes['uso'] = $uso;
            }
        }

        if (array_key_exists('nombre', $attributes)) {
            $nombre = SpaceNameNormalizationRule::normalize((string) $attributes['nombre']);

            if ($nombre !== $ambiente->nombre) {
                $changes['nombre'] = $nombre;
                $nameOrBranchChanged = true;
            }
        }

        if (array_key_exists('observaciones', $attributes)) {
            $value = $attributes['observaciones'] === null
                ? null
                : trim((string) $attributes['observaciones']);

            if ($value !== $ambiente->observaciones) {
                $changes['observaciones'] = $value;
            }
        }

        if ($nameOrBranchChanged) {
            $this->uniqueness->assertAmbienteNameAvailable(
                (int) ($changes['sucursal_id'] ?? $ambiente->sucursal_id),
                (string) ($changes['nombre'] ?? $ambiente->nombre),
                $ambiente->id,
            );
        }

        return $changes;
    }
}
