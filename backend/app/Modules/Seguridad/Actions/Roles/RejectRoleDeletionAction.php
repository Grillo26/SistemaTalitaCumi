<?php

namespace App\Modules\Seguridad\Actions\Roles;

use App\Models\User;
use App\Modules\Seguridad\Exceptions\RoleDeletionNotAllowedException;
use App\Modules\Seguridad\Exceptions\RoleNotFoundException;
use Spatie\Permission\Models\Role;

/**
 * Rechazo explícito de eliminación de roles (02/RN-17).
 */
final class RejectRoleDeletionAction
{
    public function __invoke(int $roleId, User $actor): never
    {
        $role = Role::query()
            ->whereKey($roleId)
            ->where('guard_name', 'web')
            ->first();

        if ($role === null) {
            throw new RoleNotFoundException($roleId);
        }

        throw new RoleDeletionNotAllowedException($role->name);
    }
}
