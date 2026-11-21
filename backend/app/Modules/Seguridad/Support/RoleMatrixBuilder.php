<?php

namespace App\Modules\Seguridad\Support;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Construye la grilla rol × módulo × acción (02/RF-R02, 02/RF-R03).
 */
final class RoleMatrixBuilder
{
    /**
     * @return list<array<string, mixed>>
     */
    public function modules(): array
    {
        $groups = [];

        foreach (Permission::query()->where('guard_name', 'web')->orderBy('name')->get() as $permission) {
            $module = PermissionCatalog::moduleKey($permission->name);
            $action = PermissionCatalog::actionKey($permission->name);

            $groups[$module] ??= [
                'clave' => $module,
                'label' => PermissionCatalog::moduleLabel($module),
                'orden' => PermissionCatalog::moduleOrder($module),
                'acciones' => [],
            ];

            $groups[$module]['acciones'][] = [
                'id' => $permission->id,
                'codigo' => $permission->name,
                'accion' => $action,
                'label' => PermissionCatalog::actionLabel($action),
                'orden' => PermissionCatalog::actionOrder($action),
                'critico' => in_array($permission->name, PermissionCatalog::critical(), true),
            ];
        }

        $modules = array_values($groups);

        usort($modules, static fn (array $a, array $b): int => [$a['orden'], $a['label']] <=> [$b['orden'], $b['label']]);

        foreach ($modules as &$module) {
            usort($module['acciones'], static fn (array $a, array $b): int => [$a['orden'], $a['label']] <=> [$b['orden'], $b['label']]);
        }

        unset($module);

        return $modules;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function roles(User $actor): array
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => $this->roleData($role, $actor))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function role(Role $role, User $actor): array
    {
        $role->load('permissions:id,name');

        return $this->roleData($role, $actor);
    }

    /**
     * @return array<string, mixed>
     */
    private function roleData(Role $role, User $actor): array
    {
        return [
            'id' => $role->id,
            'nombre' => $role->name,
            'label' => RoleLabel::for($role->name),
            'version' => (int) $role->version,
            'permisos' => $role->permissions->pluck('id')->map(static fn (mixed $id): int => (int) $id)->values()->all(),
            'total_permisos' => $role->permissions->count(),
            'es_superadmin' => (bool) $role->es_superadmin,
            'can' => [
                'edit' => $actor->can(SecurityPermission::ROLE_EDIT_PERMISSIONS),
            ],
        ];
    }
}
