<?php

namespace App\Modules\Seguridad\Exceptions;

/**
 * El último Superadmin activo no puede perder su rol ni ser desactivado
 * (02/RF-R08 / 02/RN-16).
 */
final class LastSuperadminProtectedException extends SeguridadDomainException
{
    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'LAST_SUPERADMIN_PROTECTED';
    }
}
