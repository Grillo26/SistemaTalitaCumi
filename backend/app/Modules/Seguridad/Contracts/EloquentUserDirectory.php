<?php

namespace App\Modules\Seguridad\Contracts;

use App\Models\User;
use App\Modules\Seguridad\Exceptions\InactiveUserException;
use App\Modules\Seguridad\Exceptions\UserNotFoundException;

/**
 * Implementación Eloquent del directorio de usuarios (02/RF-U14).
 */
final class EloquentUserDirectory implements UserDirectoryContract
{
    public function existe(int $usuarioId): bool
    {
        return User::query()->whereKey($usuarioId)->exists();
    }

    public function estaActivo(int $usuarioId): bool
    {
        return User::query()->whereKey($usuarioId)->where('activo', true)->exists();
    }

    public function asegurarActivo(int $usuarioId): void
    {
        $user = User::query()->find($usuarioId);

        if ($user === null) {
            throw new UserNotFoundException($usuarioId);
        }

        if (! $user->activo) {
            throw new InactiveUserException($user->id, $user->name);
        }
    }
}
