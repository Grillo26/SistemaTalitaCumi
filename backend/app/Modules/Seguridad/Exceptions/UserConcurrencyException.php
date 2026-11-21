<?php

namespace App\Modules\Seguridad\Exceptions;

/**
 * Edición concurrente de usuario (02/RF-U08, 02/RF-R13).
 */
final class UserConcurrencyException extends SeguridadDomainException
{
    public function __construct()
    {
        parent::__construct('El usuario fue modificado por otro usuario. Recargá los datos antes de guardar.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'USER_VERSION_CONFLICT';
    }
}
