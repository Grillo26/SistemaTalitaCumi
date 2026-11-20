<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Events\AmbienteCreated;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Organizacion\Models\Ambiente;
use App\Modules\Organizacion\Rules\SpaceInitialStatusRule;
use App\Modules\Organizacion\Rules\SpaceNameNormalizationRule;
use App\Modules\Organizacion\Support\SpaceSnapshot;
use App\Modules\Organizacion\Support\SpaceUniquenessGuard;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Crear ambiente (RF-006/RF-007 / RN-004).
 */
final class CreateAmbienteAction
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly BranchDirectoryContract $branches,
        private readonly SpaceUniquenessGuard $uniqueness,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(array $attributes, ?int $actorId, bool $hasGlobalScope): Ambiente
    {
        $sucursalId = (int) $attributes['sucursal_id'];
        $nombre = SpaceNameNormalizationRule::normalize((string) $attributes['nombre']);
        $observaciones = $attributes['observaciones'] ?? null;
        $activo = SpaceInitialStatusRule::resolve($attributes['activo'] ?? null);

        if (! $hasGlobalScope && ($actorId === null || ! $this->access->puedeAcceder($actorId, $sucursalId))) {
            throw new BranchAccessDeniedException;
        }

        return DB::transaction(function () use (
            $sucursalId,
            $nombre,
            $observaciones,
            $activo,
            $attributes,
            $actorId,
        ): Ambiente {
            $this->branches->asegurarActiva($sucursalId);
            $this->uniqueness->assertAmbienteNameAvailable($sucursalId, $nombre);

            try {
                $ambiente = Ambiente::query()->create([
                    'sucursal_id' => $sucursalId,
                    'nombre' => $nombre,
                    'uso' => (string) $attributes['uso'],
                    'observaciones' => $observaciones === null ? null : trim((string) $observaciones),
                    'activo' => $activo,
                    'version' => 1,
                ]);
            } catch (QueryException $exception) {
                $this->uniqueness->translateUniqueViolation($exception);

                throw $exception;
            }

            $ambiente->load('sucursal');

            event(new AmbienteCreated(
                ambienteId: $ambiente->id,
                snapshot: SpaceSnapshot::fromAmbiente($ambiente),
                actorId: $actorId,
            ));

            return $ambiente->fresh('sucursal');
        });
    }
}
