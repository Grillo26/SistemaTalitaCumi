<?php

namespace App\Modules\Profesionales\Actions\Directorio;

use App\Models\User;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Profesionales\Actions\Directorio\Concerns\AssertsBranchScope;
use App\Modules\Profesionales\Events\Directorio\ProfesionalSucursalChanged;
use App\Modules\Profesionales\Exceptions\ProfesionalSucursalConcurrencyException;
use App\Modules\Profesionales\Exceptions\ProfesionalSucursalNotFoundException;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Models\ProfesionalSucursal;
use App\Modules\Profesionales\Rules\ProfesionalSucursalStatusChangeRule;
use App\Modules\Profesionales\Support\ProfesionalSucursalSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Desactivar la relación profesional–sucursal con motivo (03/RF-E06 / 03/RN-20).
 */
final class DeactivateProfesionalSucursalAction
{
    use AssertsBranchScope;

    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly ProfesionalSucursalStatusChangeRule $rule,
    ) {}

    public function __invoke(
        Profesional $profesional,
        int $sucursalId,
        string $reason,
        int $expectedVersion,
        User $actor,
        ?string $ip = null,
    ): ProfesionalSucursal {
        $this->assertBranchInScope($this->access, $actor, $sucursalId);

        $reason = $this->rule->reason($reason);

        return DB::transaction(function () use ($profesional, $sucursalId, $reason, $expectedVersion, $actor, $ip): ProfesionalSucursal {
            $relation = ProfesionalSucursal::query()
                ->where('profesional_id', $profesional->id)
                ->where('sucursal_id', $sucursalId)
                ->lockForUpdate()
                ->first();

            if ($relation === null) {
                throw new ProfesionalSucursalNotFoundException($profesional->id, $sucursalId);
            }

            if ($relation->version !== $expectedVersion) {
                throw new ProfesionalSucursalConcurrencyException;
            }

            $this->rule->assertDifferent((bool) $relation->activo, false);

            $before = ProfesionalSucursalSnapshot::from($relation);

            $relation->forceFill([
                'activo' => false,
                'motivo' => $reason,
                'version' => $relation->version + 1,
            ])->save();

            $relation->refresh()->load('sucursal');

            event(new ProfesionalSucursalChanged(
                profesionalId: $profesional->id,
                sucursalId: $sucursalId,
                before: $before,
                after: ProfesionalSucursalSnapshot::from($relation),
                action: 'DESACTIVAR',
                reason: $reason,
                actorId: $actor->id,
                ip: $ip,
            ));

            return $relation;
        });
    }
}
