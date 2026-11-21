<?php

namespace App\Modules\Seguridad\Actions\Usuarios;

use App\Models\User;
use App\Modules\Seguridad\Events\UserUpdated;
use App\Modules\Seguridad\Exceptions\UserConcurrencyException;
use App\Modules\Seguridad\Exceptions\UserNotFoundException;
use App\Modules\Seguridad\Exceptions\UserRoleRequiredException;
use App\Modules\Seguridad\Rules\LastSuperadminRule;
use App\Modules\Seguridad\Rules\UserIdentityNormalizationRule;
use App\Modules\Seguridad\Support\SuperadminGuard;
use App\Modules\Seguridad\Support\UserAccessScope;
use App\Modules\Seguridad\Support\UserSnapshot;
use App\Modules\Seguridad\Support\UserUniquenessGuard;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Edición de datos, rol y sucursales (02/RF-U08, 02/RF-R12, 02/RF-U12).
 *
 * Si cambia el rol, invalida todas las sesiones (02/RN-14).
 */
final class UpdateUserAction
{
    public function __construct(
        private readonly UserUniquenessGuard $uniqueness,
        private readonly UserAccessScope $scope,
        private readonly SuperadminGuard $superadminGuard,
        private readonly LastSuperadminRule $lastSuperadminRule,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        User $target,
        array $attributes,
        int $expectedVersion,
        User $actor,
        ?string $ip,
    ): User {
        $this->scope->assertCanAccess($actor, $target);

        return DB::transaction(function () use ($target, $attributes, $expectedVersion, $actor, $ip): User {
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

            $before = UserSnapshot::from($user);
            $changes = [];
            $changedFields = [];
            $roleChanged = false;
            $branchesChanged = false;
            $sessionsInvalidated = 0;

            if (array_key_exists('rol_id', $attributes)) {
                $role = $this->resolveRole((int) $attributes['rol_id']);
                $currentRole = $user->roles()->first();

                if ($currentRole?->id !== $role->id) {
                    $this->lastSuperadminRule->assertCanChangeRole(
                        targetIsSuperadmin: $this->superadminGuard->isSuperadmin($user),
                        targetIsActive: (bool) $user->activo,
                        newRoleIsSuperadmin: (bool) $role->es_superadmin,
                        activeSuperadmins: $activeSuperadmins,
                    );

                    $user->syncRoles([$role]);
                    $user->unsetRelation('roles')->unsetRelation('permissions');
                    $roleChanged = true;
                    $changedFields[] = 'rol';
                }
            }

            if (array_key_exists('sucursales', $attributes)) {
                $branchIds = array_values(array_unique(array_map('intval', $attributes['sucursales'])));
                $this->scope->assertCanAssignBranches($actor, $branchIds);

                $currentBranches = $user->branches()
                    ->pluck('sucursal.id')
                    ->map(static fn (mixed $id): int => (int) $id)
                    ->sort()
                    ->values()
                    ->all();

                $newBranches = collect($branchIds)->sort()->values()->all();

                if ($currentBranches !== $newBranches) {
                    $user->branches()->sync($branchIds);
                    $branchesChanged = true;
                    $changedFields[] = 'sucursales';
                }
            }

            foreach (['name', 'email', 'username', 'documento'] as $field) {
                if (! array_key_exists($field, $attributes)) {
                    continue;
                }

                $value = match ($field) {
                    'name' => UserIdentityNormalizationRule::name((string) $attributes[$field]),
                    'email' => UserIdentityNormalizationRule::emailOrNull($attributes[$field] ?? null),
                    'username' => UserIdentityNormalizationRule::username((string) $attributes[$field]),
                    'documento' => UserIdentityNormalizationRule::documento((string) $attributes[$field]),
                };

                if ($value !== $user->{$field}) {
                    if (in_array($field, ['email', 'username', 'documento'], true) && $value !== null) {
                        $this->uniqueness->assertAvailable($field, $value, $user->id);
                    }

                    $changes[$field] = $value;
                    $changedFields[] = $field;
                }
            }

            if ($changes === [] && ! $roleChanged && ! $branchesChanged) {
                return $user;
            }

            $user->fill($changes);
            $user->version = $user->version + 1;

            try {
                $user->save();
            } catch (QueryException $exception) {
                $this->uniqueness->translateUniqueViolation($exception);

                throw $exception;
            }

            if ($roleChanged) {
                $sessionsInvalidated = $user->tokens()->delete();
            }

            $user->refresh();

            event(new UserUpdated(
                userId: $user->id,
                before: $before,
                after: UserSnapshot::from($user),
                changedFields: $changedFields,
                roleChanged: $roleChanged,
                sessionsInvalidated: $sessionsInvalidated,
                actorId: $actor->id,
                ip: $ip,
            ));

            return $user;
        });
    }

    private function resolveRole(int $roleId): Role
    {
        $role = Role::query()
            ->whereKey($roleId)
            ->where('guard_name', 'web')
            ->first();

        if ($role === null) {
            throw new UserRoleRequiredException;
        }

        return $role;
    }
}
