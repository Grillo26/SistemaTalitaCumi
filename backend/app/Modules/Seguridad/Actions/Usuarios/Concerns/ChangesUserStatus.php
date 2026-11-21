<?php

namespace App\Modules\Seguridad\Actions\Usuarios\Concerns;

use App\Models\User;
use App\Modules\Organizacion\Support\BranchPermission;
use App\Modules\Seguridad\Events\UserStatusChanged;
use App\Modules\Seguridad\Exceptions\UserBranchRequiredException;
use App\Modules\Seguridad\Exceptions\UserConcurrencyException;
use App\Modules\Seguridad\Exceptions\UserNotFoundException;
use App\Modules\Seguridad\Exceptions\UserRoleRequiredException;
use App\Modules\Seguridad\Rules\UserStatusChangeRule;
use App\Modules\Seguridad\Support\UserSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Cambio de estado de usuario con motivo y protección del último Superadmin
 * (02/RF-U09 / 02/RN-05, 02/RN-10, 02/RN-16).
 */
trait ChangesUserStatus
{
    protected function changeStatus(
        User $target,
        bool $targetStatus,
        ?string $reason,
        int $expectedVersion,
        User $actor,
        ?string $ip,
    ): User {
        $this->scope->assertCanAccess($actor, $target);

        $rule = new UserStatusChangeRule;
        $reason = $rule->reason($reason, $targetStatus);

        return DB::transaction(function () use ($target, $targetStatus, $reason, $expectedVersion, $actor, $ip, $rule): User {
            $activeSuperadmins = $this->superadminGuard->countActiveForUpdate();

            $user = User::query()
                ->whereKey($target->id)
                ->lockForUpdate()
                ->first();

            if ($user === null) {
                throw new UserNotFoundException($target->id);
            }

            if ($user->version !== $expectedVersion) {
                throw new UserConcurrencyException;
            }

            $rule->assertDifferent((bool) $user->activo, $targetStatus);

            if ($targetStatus) {
                if (! $user->roles()->exists()) {
                    throw new UserRoleRequiredException;
                }

                if (! $user->can(BranchPermission::GLOBAL_SCOPE)
                    && ! $user->branches()->where('sucursal.activo', true)->exists()) {
                    throw new UserBranchRequiredException;
                }
            } else {
                $this->lastSuperadminRule->assertCanDeactivate(
                    targetIsSuperadmin: $this->superadminGuard->isSuperadmin($user),
                    targetIsActive: (bool) $user->activo,
                    activeSuperadmins: $activeSuperadmins,
                );
            }

            $before = UserSnapshot::from($user);

            $user->forceFill([
                'activo' => $targetStatus,
                'motivo_estado' => $targetStatus ? null : $reason,
                'estado_cambiado_en' => now(),
                'version' => $user->version + 1,
            ])->save();

            $sessionsInvalidated = $targetStatus ? 0 : $user->tokens()->delete();

            $user->refresh();

            event(new UserStatusChanged(
                userId: $user->id,
                before: $before,
                after: UserSnapshot::from($user),
                active: $targetStatus,
                reason: $reason,
                sessionsInvalidated: $sessionsInvalidated,
                actorId: $actor->id,
                ip: $ip,
            ));

            return $user;
        });
    }
}
