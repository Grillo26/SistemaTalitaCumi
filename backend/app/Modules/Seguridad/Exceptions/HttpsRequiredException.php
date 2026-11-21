<?php

namespace App\Modules\Seguridad\Exceptions;

/**
 * La configuración exige HTTPS y la petición llegó por HTTP (02/RF-A14).
 */
final class HttpsRequiredException extends SeguridadDomainException
{
    public function __construct()
    {
        parent::__construct('Esta operación requiere una conexión HTTPS.');
    }

    public function status(): int
    {
        return 403;
    }

    public function errorCode(): string
    {
        return 'HTTPS_REQUIRED';
    }
}
