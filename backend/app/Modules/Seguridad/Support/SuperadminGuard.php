<?php

namespace App\Modules\Seguridad\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Conteo y bloqueo del conjunto de Superadmins activos (02/RN-16).
 *
 * Bloquea las filas en orden de id para serializar dos desactivaciones
 * concurrentes del último Superadmin.
 */
final class SuperadminGuard
{
    public function isSuperadmin(User $user): bool
    {
        return $user->roles()
            ->where('roles.es_superadmin', true)
            ->exists();
    }

    public function countActiveForUpdate(): int
    {
        return User::query()
            ->where('activo', true)
            ->whereHas(
                'roles',
                static fn (Builder $roles) => $roles->where('roles.es_superadmin', true),
            )
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id'])
            ->count();
    }
}
