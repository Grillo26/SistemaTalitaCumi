<?php

namespace App\Modules\Seguridad\Support;

use App\Models\User;
use App\Modules\Seguridad\Exceptions\DuplicateUserFieldException;
use Illuminate\Database\QueryException;

/**
 * Unicidad de documento, correo y nombre de usuario (02/RF-U02 / 02/RN-01).
 */
final class UserUniquenessGuard
{
    public function assertAvailable(string $field, string $value, ?int $ignoreUserId = null): void
    {
        $column = match ($field) {
            'email' => 'email',
            'username' => 'username',
            'documento' => 'documento',
            default => throw new \InvalidArgumentException("Campo de unicidad no soportado: {$field}"),
        };

        $query = User::query()->whereRaw(
            "lower({$column}) = ?",
            [mb_strtolower($value)],
        );

        if ($ignoreUserId !== null) {
            $query->whereKeyNot($ignoreUserId);
        }

        if ($query->exists()) {
            throw new DuplicateUserFieldException($field);
        }
    }

    public function translateUniqueViolation(QueryException $exception): void
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'users_email_unique') || str_contains($message, 'users.email')) {
            throw new DuplicateUserFieldException('email');
        }

        if (str_contains($message, 'users_username_unique') || str_contains($message, 'users.username')) {
            throw new DuplicateUserFieldException('username');
        }

        if (str_contains($message, 'users_documento_unique') || str_contains($message, 'users.documento')) {
            throw new DuplicateUserFieldException('documento');
        }
    }
}
