<?php

namespace App\Modules\Seguridad\Actions\Usuarios;

use App\Models\User;
use App\Modules\Seguridad\Events\UserUnlocked;
use App\Modules\Seguridad\Exceptions\UserConcurrencyException;
use App\Modules\Seguridad\Exceptions\UserNotBlockedException;
use App\Modules\Seguridad\Exceptions\UserNotFoundException;
use App\Modules\Seguridad\Support\UserAccessScope;
use App\Modules\Seguridad\Support\UserSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Desbloqueo manual anticipado por Superadmin (02/RF-A06 / 02/RN-13).
 */
final class UnlockUserAction
{
    public function __construct(private readonly UserAccessScope $scope) {}

    public function __invoke(
        User $target,
        string $reason,
        int $expectedVersion,
        User $actor,
        ?string $ip,
    ): User {
        $this->scope->assertCanAccess($actor, $target);

        return DB::transaction(function () use ($target, $reason, $expectedVersion, $actor, $ip): User {
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

            if (! $user->estaBloqueado() && $user->intentos_fallidos === 0) {
                throw new UserNotBlockedException;
            }

            $before = UserSnapshot::from($user);

            $user->forceFill([
                'intentos_fallidos' => 0,
                'bloqueado_hasta' => null,
                'version' => $user->version + 1,
            ])->save();

            $user->refresh();

            event(new UserUnlocked(
                userId: $user->id,
                before: $before,
                after: UserSnapshot::from($user),
                reason: $reason,
                actorId: $actor->id,
                ip: $ip,
            ));

            return $user;
        });
    }
}
