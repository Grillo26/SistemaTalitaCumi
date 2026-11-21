<?php

namespace App\Modules\Seguridad\Queries\Usuarios;

use App\Models\User;
use App\Modules\Seguridad\Exceptions\UserNotFoundException;
use App\Modules\Seguridad\Support\UserAccessScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ficha de usuario con rol, sucursales y sesiones (02/RF-U07).
 */
final class GetUserQuery
{
    public function __construct(private readonly UserAccessScope $scope) {}

    public function __invoke(int $userId, User $actor): User
    {
        $user = User::query()
            ->with([
                'roles:id,name',
                'branches:id,codigo,nombre',
            ])
            ->withCount([
                'tokens as sesiones_activas' => static fn (Builder $tokens) => $tokens->where(
                    static fn (Builder $tokens) => $tokens
                        ->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now()),
                ),
            ])
            ->find($userId);

        if ($user === null) {
            throw new UserNotFoundException($userId);
        }

        $this->scope->assertCanAccess($actor, $user);

        return $user;
    }
}
