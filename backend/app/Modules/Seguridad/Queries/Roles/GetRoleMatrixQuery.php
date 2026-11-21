<?php

namespace App\Modules\Seguridad\Queries\Roles;

use App\Models\User;
use App\Modules\Seguridad\Exceptions\RoleNotFoundException;
use App\Modules\Seguridad\Support\RoleMatrixBuilder;
use Spatie\Permission\Models\Role;

/**
 * Matriz de un rol (02/RF-R02, 02/RF-R03).
 */
final class GetRoleMatrixQuery
{
    public function __construct(private readonly RoleMatrixBuilder $builder) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(int $roleId, User $actor): array
    {
        $role = Role::query()
            ->whereKey($roleId)
            ->where('guard_name', 'web')
            ->first();

        if ($role === null) {
            throw new RoleNotFoundException($roleId);
        }

        return [
            'role' => $this->builder->role($role, $actor),
            'modulos' => $this->builder->modules(),
        ];
    }
}
