<?php

namespace App\Modules\Seguridad\Support;

/**
 * Regla pura de política de contraseñas (02/RF-A02 / 02/RN-12).
 *
 * No usa DB, Request ni facades: recibe la configuración ya resuelta.
 */
final class PasswordPolicy
{
    public function __construct(private readonly PasswordPolicyConfig $config) {}

    /**
     * @return list<string> errores en español; lista vacía si cumple
     */
    public function validate(string $password): array
    {
        $errors = [];
        $length = mb_strlen($password);

        if ($length < $this->config->minLength) {
            $errors[] = sprintf('La contraseña debe tener al menos %d caracteres.', $this->config->minLength);
        }

        // bcrypt trunca a 72 bytes; se rechaza antes para no dar una falsa sensación de seguridad.
        if (strlen($password) > 72) {
            $errors[] = 'La contraseña no puede superar los 72 bytes.';
        }

        if ($this->config->requireUppercase && preg_match('/[A-Z]/', $password) !== 1) {
            $errors[] = 'La contraseña debe incluir al menos una letra mayúscula.';
        }

        if ($this->config->requireLowercase && preg_match('/[a-z]/', $password) !== 1) {
            $errors[] = 'La contraseña debe incluir al menos una letra minúscula.';
        }

        if ($this->config->requireNumber && preg_match('/[0-9]/', $password) !== 1) {
            $errors[] = 'La contraseña debe incluir al menos un número.';
        }

        if ($this->config->requireSymbol && preg_match('/[^A-Za-z0-9]/', $password) !== 1) {
            $errors[] = 'La contraseña debe incluir al menos un símbolo.';
        }

        return $errors;
    }
}
