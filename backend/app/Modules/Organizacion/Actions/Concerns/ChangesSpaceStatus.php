<?php

namespace App\Modules\Organizacion\Actions\Concerns;

use App\Modules\Organizacion\Exceptions\SpaceConcurrencyException;
use App\Modules\Organizacion\Rules\SpaceStatusChangeRule;
use Illuminate\Database\Eloquent\Model;

trait ChangesSpaceStatus
{
    /**
     * @param  callable(Model): array<string, mixed>  $snapshot
     * @param  callable(Model, array<string, mixed>, array<string, mixed>, bool, ?string, ?int): object  $eventFactory
     */
    protected function changeStatus(
        string $entityLabel,
        Model $model,
        bool $targetStatus,
        ?string $reason,
        int $expectedVersion,
        ?int $actorId,
        callable $snapshot,
        callable $eventFactory,
    ): Model {
        if ($model->version !== $expectedVersion) {
            throw new SpaceConcurrencyException($entityLabel);
        }

        $rule = new SpaceStatusChangeRule;
        $normalizedReason = null;

        if (! $targetStatus) {
            $normalizedReason = $rule->reason((string) $reason);
        }

        $rule->assertDifferent((bool) $model->activo, $targetStatus);

        $before = $snapshot($model);

        $model->activo = $targetStatus;
        $model->version = $model->version + 1;
        $model->save();
        $model->refresh();

        $after = $snapshot($model);

        event($eventFactory($model, $before, $after, $targetStatus, $normalizedReason, $actorId));

        return $model;
    }
}
