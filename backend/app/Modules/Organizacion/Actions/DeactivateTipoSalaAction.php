<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Actions\Concerns\ChangesSpaceStatus;
use App\Modules\Organizacion\Events\TipoSalaStatusChanged;
use App\Modules\Organizacion\Exceptions\TipoSalaNotFoundException;
use App\Modules\Organizacion\Models\Sala;
use App\Modules\Organizacion\Models\TipoSala;
use App\Modules\Organizacion\Rules\TipoSalaDeactivationRule;
use App\Modules\Organizacion\Support\SpaceSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Desactivar tipo de sala (RF-002).
 */
final class DeactivateTipoSalaAction
{
    use ChangesSpaceStatus;

    public function __invoke(int $id, ?string $reason, int $expectedVersion, ?int $actorId): TipoSala
    {
        return DB::transaction(function () use ($id, $reason, $expectedVersion, $actorId): TipoSala {
            $tipoSala = TipoSala::query()->whereKey($id)->lockForUpdate()->first();

            if ($tipoSala === null) {
                throw new TipoSalaNotFoundException($id);
            }

            $activeSalas = Sala::query()
                ->where('tipo_sala_id', $tipoSala->id)
                ->where('activo', true)
                ->count();

            TipoSalaDeactivationRule::assertCanDeactivate(
                $activeSalas > 0,
                $tipoSala->nombre,
                $activeSalas,
            );

            return $this->changeStatus(
                entityLabel: 'tipo de sala',
                model: $tipoSala,
                targetStatus: false,
                reason: $reason,
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
