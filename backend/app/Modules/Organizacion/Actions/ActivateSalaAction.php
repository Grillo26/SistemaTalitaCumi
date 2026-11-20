<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Actions\Concerns\ChangesSpaceStatus;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Events\SalaStatusChanged;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Organizacion\Exceptions\SalaNotFoundException;
use App\Modules\Organizacion\Models\Sala;
use App\Modules\Organizacion\Support\SpaceSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Activar sala (RF-011).
 */
final class ActivateSalaAction
{
    use ChangesSpaceStatus;

    public function __construct(private readonly UserBranchAccessContract $access) {}

    public function __invoke(int $id, int $expectedVersion, ?int $actorId, bool $hasGlobalScope): Sala
    {
        return DB::transaction(function () use ($id, $expectedVersion, $actorId, $hasGlobalScope): Sala {
            $sala = Sala::query()->whereKey($id)->lockForUpdate()->first();

            if ($sala === null) {
                throw new SalaNotFoundException($id);
            }

            if (! $hasGlobalScope
                && ($actorId === null || ! $this->access->puedeAcceder($actorId, $sala->sucursal_id))) {
                throw new BranchAccessDeniedException;
            }

            return $this->changeStatus(
                entityLabel: 'sala',
                model: $sala,
                targetStatus: true,
                reason: null,
                expectedVersion: $expectedVersion,
                actorId: $actorId,
                snapshot: fn (Sala $model): array => SpaceSnapshot::fromSala($model),
                eventFactory: fn (Sala $model, array $before, array $after, bool $activo, ?string $reason, ?int $actorId): SalaStatusChanged => new SalaStatusChanged(
                    $model->id, $before, $after, $activo, $reason, $actorId,
                ),
            );
        });
    }
}
