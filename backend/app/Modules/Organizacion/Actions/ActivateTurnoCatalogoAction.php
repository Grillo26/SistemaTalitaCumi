<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Actions\Concerns\ChangesSpaceStatus;
use App\Modules\Organizacion\Events\TurnoCatalogoStatusChanged;
use App\Modules\Organizacion\Exceptions\TurnoCatalogoNotFoundException;
use App\Modules\Organizacion\Models\TurnoCatalogo;
use App\Modules\Organizacion\Support\SpaceSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Activar turno operativo (RF-003).
 */
final class ActivateTurnoCatalogoAction
{
    use ChangesSpaceStatus;

    public function __invoke(int $id, int $expectedVersion, ?int $actorId): TurnoCatalogo
    {
        return DB::transaction(function () use ($id, $expectedVersion, $actorId): TurnoCatalogo {
            $turno = TurnoCatalogo::query()->whereKey($id)->lockForUpdate()->first();

            if ($turno === null) {
                throw new TurnoCatalogoNotFoundException($id);
            }

            return $this->changeStatus(
                entityLabel: 'turno',
                model: $turno,
                targetStatus: true,
                reason: null,
                expectedVersion: $expectedVersion,
                actorId: $actorId,
                snapshot: fn (TurnoCatalogo $model): array => SpaceSnapshot::fromTurno($model),
                eventFactory: fn (TurnoCatalogo $model, array $before, array $after, bool $activo, ?string $reason, ?int $actorId): TurnoCatalogoStatusChanged => new TurnoCatalogoStatusChanged(
                    $model->id, $before, $after, $activo, $reason, $actorId,
                ),
            );
        });
    }
}
