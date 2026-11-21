<?php

namespace App\Modules\Seguridad\Exceptions;

use Illuminate\Support\Facades\Log;

/**
 * Falta una clave de configuración de seguridad (RF-A02 / RN-12).
 *
 * Es fail-closed: nunca se inventa un valor por defecto.
 */
final class SecurityConfigurationException extends SeguridadDomainException
{
    public function __construct(public readonly string $clave)
    {
        Log::critical('Falta una clave de configuración de seguridad.', [
            'clave' => $clave,
        ]);

        parent::__construct('La configuración de seguridad no está disponible. Contactá al administrador.');
    }

    public function status(): int
    {
        return 503;
    }

    public function errorCode(): string
    {
        return 'SECURITY_CONFIGURATION_MISSING';
    }
}
