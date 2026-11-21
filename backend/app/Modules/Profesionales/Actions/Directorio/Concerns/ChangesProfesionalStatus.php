<?php

namespace App\Modules\Profesionales\Actions\Directorio\Concerns;

use App\Models\User;
use App\Modules\Profesionales\Events\Directorio\ProfesionalStatusChanged;
use App\Modules\Profesionales\Exceptions\ProfesionalConcurrencyException;
use App\Modules\Profesionales\Exceptions\ProfesionalNotFoundException;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Rules\ProfesionalStatusChangeRule;
use App\Modules\Profesionales\Support\ProfesionalSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Cambio de estado del profesional con motivo y concurrencia (03/RF-D07 / 03/RN-20).
 */
trait ChangesProfesionalStatus
{
    protected function changeStatus(
        Profesional $profesional,
        bool $targetStatus,
        string $reason,
        int $expectedVersion,
        User $actor,
        ?string $ip,
    ): Profesional {
        $this->scope->assertCanAccess($actor, $profesional);

        $rule = new ProfesionalStatusChangeRule;
        $reason = $rule->reason($reason, $targetStatus);

        return DB::transaction(function () use ($profesional, $targetStatus, $reason, $expectedVersion, $actor, $ip, $rule): Profesional {
            $current = Profesional::query()
                ->whereKey($profesional->id)
                ->lockForUpdate()
                ->first();

            if ($current === null) {
                throw new ProfesionalNotFoundException($profesional->id);
            }

            if ($current->version !== $expectedVersion) {
                throw new ProfesionalConcurrencyException;
            }

            $rule->assertDifferent((bool) $current->activo, $targetStatus);

            $before = ProfesionalSnapshot::from($current);

            $current->forceFill([
                'activo' => $targetStatus,
                'motivo_estado' => $targetStatus ? null : $reason,
                'estado_cambiado_en' => now(),
                'version' => $current->version + 1,
            ])->save();

            $current->refresh();

            event(new ProfesionalStatusChanged(
                profesionalId: $current->id,
                before: $before,
                after: ProfesionalSnapshot::from($current),
                activo: $targetStatus,
                reason: $reason,
                actorId: $actor->id,
                ip: $ip,
            ));

            return $current;
        });
    }
}
