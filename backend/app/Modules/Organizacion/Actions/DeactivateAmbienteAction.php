<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Actions\Concerns\ChangesSpaceStatus;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Events\AmbienteStatusChanged;
use App\Modules\Organizacion\Exceptions\AmbienteNotFoundException;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Organizacion\Models\Ambiente;
use App\Modules\Organizacion\Support\SpaceSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Desactivar ambiente (RF-011 / RN-010).
 */
final class DeactivateAmbienteAction
{
    use ChangesSpaceStatus;

    public function __construct(private readonly UserBranchAccessContract $access) {}

    public function __invoke(
        int $id,
        ?string $reason,
        int $expectedVersion,
        ?int $actorId,
        bool $hasGlobalScope,
    ): Ambiente {
        return DB::transaction(function () use ($id, $reason, $expectedVersion, $actorId, $hasGlobalScope): Ambiente {
            $ambiente = Ambiente::query()->whereKey($id)->lockForUpdate()->first();

            if ($ambiente === null) {
                throw new AmbienteNotFoundException($id);
            }

            if (! $hasGlobalScope
                && ($actorId === null || ! $this->access->puedeAcceder($actorId, $ambiente->sucursal_id))) {
                throw new BranchAccessDeniedException;
            }

            return $this->changeStatus(
                entityLabel: 'ambiente',
                model: $ambiente,
                targetStatus: false,
                reason: $reason,
                expectedVersion: $expectedVersion,
                actorId: $actorId,
                snapshot: fn (Ambiente $model): array => SpaceSnapshot::fromAmbiente($model),
                eventFactory: fn (Ambiente $model, array $before, array $after, bool $activo, ?string $reason, ?int $actorId): AmbienteStatusChanged => new AmbienteStatusChanged(
                    $model->id, $before, $after, $activo, $reason, $actorId,
                ),
            );
        });
    }
}
