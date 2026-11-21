<?php

namespace App\Modules\Seguridad\Actions\Roles;

use App\Models\User;
use App\Modules\Seguridad\Events\RolePermissionsUpdated;
use App\Modules\Seguridad\Exceptions\CriticalPermissionProtectedException;
use App\Modules\Seguridad\Exceptions\InvalidPermissionException;
use App\Modules\Seguridad\Exceptions\MatrixConcurrencyException;
use App\Modules\Seguridad\Exceptions\MatrixUnchangedException;
use App\Modules\Seguridad\Exceptions\RoleNotFoundException;
use App\Modules\Seguridad\Support\PermissionCatalog;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Edición de la matriz de permisos de un rol (02/RF-R04, 02/RF-R08, 02/RF-R13).
 *
 * Usa `version` para concurrencia optimista, audita antes/después y limpia
 * la caché de Spatie. El rol Superadmin conserva los permisos críticos.
 */
final class UpdateRolePermissionsAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        int $roleId,
        array $attributes,
        User $actor,
        ?string $ip,
    ): Role {
        $expectedVersion = (int) $attributes['version'];
        $permissionIds = array_values(array_unique(array_map('intval', $attributes['permisos'] ?? [])));

        return DB::transaction(function () use ($roleId, $expectedVersion, $permissionIds, $actor, $ip): Role {
            $role = Role::query()
                ->whereKey($roleId)
                ->where('guard_name', 'web')
                ->lockForUpdate()
                ->first();

            if ($role === null) {
                throw new RoleNotFoundException($roleId);
            }

            if ((int) $role->version !== $expectedVersion) {
                throw new MatrixConcurrencyException;
            }

            $permissions = Permission::query()
                ->whereIn('id', $permissionIds)
                ->where('guard_name', 'web')
                ->orderBy('name')
                ->get();

            if ($permissions->count() !== count($permissionIds)) {
                throw new InvalidPermissionException;
            }

            $beforeNames = $role->permissions()
                ->where('guard_name', 'web')
                ->pluck('name')
                ->sort()
                ->values()
                ->all();

            $afterNames = $permissions->pluck('name')->sort()->values()->all();

            if ($role->es_superadmin) {
                $missing = array_values(array_diff(PermissionCatalog::critical(), $afterNames));

                if ($missing !== []) {
                    throw new CriticalPermissionProtectedException($missing);
                }
            }

            if ($beforeNames === $afterNames) {
                throw new MatrixUnchangedException;
            }

            $beforeVersion = (int) $role->version;

            $role->syncPermissions($permissions);

            $role->version = $beforeVersion + 1;
            $role->save();

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            event(new RolePermissionsUpdated(
                roleId: $role->id,
                before: $beforeNames,
                after: $afterNames,
                beforeVersion: $beforeVersion,
                version: (int) $role->version,
                actorId: $actor->id,
                ip: $ip,
                motivo: null,
                baseline: false,
            ));

            return $role->refresh();
        });
    }
}
