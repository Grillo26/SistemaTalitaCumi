<?php

namespace App\Modules\Seguridad\Queries\Roles;

use App\Models\User;
use App\Modules\Seguridad\Support\RoleMatrixBuilder;
use Spatie\Permission\Models\Permission;

/**
 * Matriz completa: roles, módulos y acciones (02/RF-R02, 02/RF-R03).
 */
final class ListRolesMatrixQuery
{
    public function __construct(private readonly RoleMatrixBuilder $builder) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(User $actor): array
    {
        return [
            'roles' => $this->builder->roles($actor),
            'modulos' => $this->builder->modules(),
            'total_permisos' => Permission::query()->where('guard_name', 'web')->count(),
        ];
    }
}
