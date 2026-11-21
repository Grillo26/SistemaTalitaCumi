<?php

namespace App\Modules\Seguridad\Contracts;

use App\Modules\Seguridad\Exceptions\InactiveUserException;
use App\Modules\Seguridad\Exceptions\UserNotFoundException;

/**
 * Directorio mínimo de usuarios para módulos consumidores (02/RF-U14).
 *
 * Permite verificar existencia y estado sin acoplarse al modelo User.
 */
interface UserDirectoryContract
{
    public function existe(int $usuarioId): bool;

    public function estaActivo(int $usuarioId): bool;

    /**
     * @throws UserNotFoundException
     * @throws InactiveUserException
     */
    public function asegurarActivo(int $usuarioId): void;
}
