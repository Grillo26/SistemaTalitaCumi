<?php

namespace App\Modules\Seguridad\Actions\Usuarios;

use App\Models\User;
use App\Modules\Seguridad\Exceptions\UserDeletionNotAllowedException;
use App\Modules\Seguridad\Exceptions\UserNotFoundException;
use App\Modules\Seguridad\Support\UserAccessScope;

/**
 * Rechazo explícito de eliminación física (02/RF-U11 / 02/RN-05).
 */
final class RejectUserDeletionAction
{
    public function __construct(private readonly UserAccessScope $scope) {}

    public function __invoke(int $userId, User $actor): never
    {
        $user = User::query()->find($userId);

        if ($user === null) {
            throw new UserNotFoundException($userId);
        }

        $this->scope->assertCanAccess($actor, $user);

        throw new UserDeletionNotAllowedException($user->name);
    }
}
