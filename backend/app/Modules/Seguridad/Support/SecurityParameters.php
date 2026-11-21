<?php

namespace App\Modules\Seguridad\Support;

use App\Modules\Organizacion\Contracts\ParameterReaderContract;
use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;
use App\Modules\Organizacion\Support\ParameterValue;
use App\Modules\Seguridad\Exceptions\SecurityConfigurationException;

/**
 * Lee la política de seguridad desde `configuracion` (módulo 03).
 *
 * Si falta una clave o el tipo no coincide, falla de forma explícita:
 * nunca inventa valores por defecto (fail-closed). 02/RF-A02 / 02/RN-12.
 */
final class SecurityParameters
{
    public function __construct(private readonly ParameterReaderContract $reader) {}

    public function passwordPolicy(): PasswordPolicyConfig
    {
        return new PasswordPolicyConfig(
            minLength: $this->integer('password_longitud_minima'),
            requireUppercase: $this->boolean('password_requiere_mayuscula'),
            requireLowercase: $this->boolean('password_requiere_minuscula'),
            requireNumber: $this->boolean('password_requiere_numero'),
            requireSymbol: $this->boolean('password_requiere_simbolo'),
            historyCount: $this->integer('password_historial_cantidad'),
            expiryDays: $this->integer('password_caducidad_dias'),
        );
    }

    public function sessionInactivityMinutes(): int
    {
        return $this->integer('sesion_inactividad_minutos');
    }

    public function sessionAbsoluteMinutes(): int
    {
        return $this->integer('sesion_duracion_absoluta_minutos');
    }

    public function maxSessions(): int
    {
        return max(1, $this->integer('sesion_maximas_por_usuario'));
    }

    public function loginMaxAttempts(): int
    {
        return max(1, $this->integer('login_intentos_maximos'));
    }

    public function loginLockMinutes(): int
    {
        return max(1, $this->integer('login_bloqueo_minutos'));
    }

    public function auditRetentionYears(): int
    {
        return $this->integer('bitacora_retencion_anios');
    }

    private function integer(string $clave): int
    {
        $value = $this->value($clave)->valor;

        if (! is_int($value)) {
            throw new SecurityConfigurationException($clave);
        }

        return $value;
    }

    private function boolean(string $clave): bool
    {
        $value = $this->value($clave)->valor;

        if (! is_bool($value)) {
            throw new SecurityConfigurationException($clave);
        }

        return $value;
    }

    private function value(string $clave): ParameterValue
    {
        try {
            return $this->reader->obtener($clave);
        } catch (ParameterNotFoundException) {
            throw new SecurityConfigurationException($clave);
        }
    }
}
