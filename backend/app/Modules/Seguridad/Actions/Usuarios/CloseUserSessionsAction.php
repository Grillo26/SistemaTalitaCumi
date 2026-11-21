<?php

namespace App\Modules\Seguridad\Actions\Usuarios;

use App\Models\User;
use App\Modules\Seguridad\Events\UserSessionsClosed;
use App\Modules\Seguridad\Exceptions\UserNotFoundException;
use App\Modules\Seguridad\Support\UserAccessScope;
use Illuminate\Support\Facades\DB;

/**
 * Cierre remoto de todas las sesiones de un usuario (02/RF-A09 / 02/RN-10).
 */
final class CloseUserSessionsAction
{
    public function __construct(private readonly UserAccessScope $scope) {}

    /**
     * @return array{user: User, sessions_closed: int}
     */
    public function __invoke(User $target, string $reason, User $actor, ?string $ip): array
    {
        $this->scope->assertCanAccess($actor, $target);

        return DB::transaction(function () use ($target, $reason, $actor, $ip): array {
            $user = User::query()
                ->whereKey($target->id)
                ->lockForUpdate()
                ->first();

            if ($user === null) {
                throw new UserNotFoundException($target->id);
            }

            $sessionsClosed = $user->tokens()->delete();

            event(new UserSessionsClosed(
                userId: $user->id,
                sessionsClosed: $sessionsClosed,
                reason: $reason,
                actorId: $actor->id,
                ip: $ip,
            ));

            return [
                'user' => $user->refresh(),
                'sessions_closed' => $sessionsClosed,
            ];
        });
    }
}
