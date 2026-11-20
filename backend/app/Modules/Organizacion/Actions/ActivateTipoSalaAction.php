<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Actions\Concerns\ChangesSpaceStatus;
use App\Modules\Organizacion\Events\TipoSalaStatusChanged;
use App\Modules\Organizacion\Exceptions\TipoSalaNotFoundException;
use App\Modules\Organizacion\Models\TipoSala;
use App\Modules\Organizacion\Support\SpaceSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Activar tipo de sala (RF-002).
 */
final class ActivateTipoSalaAction
{
    use ChangesSpaceStatus;

    public function __invoke(int $id, int $expectedVersion, ?int $actorId): TipoSala
    {
        return DB::transaction(function () use ($id, $expectedVersion, $actorId): TipoSala {
            $tipoSala = TipoSala::query()->whereKey($id)->lockForUpdate()->first();

            if ($tipoSala === null) {
                throw new TipoSalaNotFoundException($id);
            }

            return $this->changeStatus(
                entityLabel: 'tipo de sala',
                model: $tipoSala,
                targetStatus: true,
                reason: null,
                expectedVersion: $expectedVersion,
                actorId: $actorId,
                snapshot: fn (TipoSala $model): array => SpaceSnapshot::fromTipoSala($model),
                eventFactory: fn (TipoSala $model, array $before, array $after, bool $activo, ?string $reason, ?int $actorId): TipoSalaStatusChanged => new TipoSalaStatusChanged(
                    $model->id, $before, $after, $activo, $reason, $actorId,
                ),
            );
        });
    }
}
