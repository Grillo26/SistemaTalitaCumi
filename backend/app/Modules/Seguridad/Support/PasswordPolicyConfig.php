<?php

namespace App\Modules\Seguridad\Support;

/**
 * Política de contraseñas resuelta desde parámetros (RF-A02 / RN-12).
 */
final class PasswordPolicyConfig
{
    public function __construct(
        public readonly int $minLength,
        public readonly bool $requireUppercase,
        public readonly bool $requireLowercase,
        public readonly bool $requireNumber,
        public readonly bool $requireSymbol,
        public readonly int $historyCount,
        public readonly int $expiryDays,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'min_length' => $this->minLength,
            'require_uppercase' => $this->requireUppercase,
            'require_lowercase' => $this->requireLowercase,
            'require_number' => $this->requireNumber,
            'require_symbol' => $this->requireSymbol,
            'history_count' => $this->historyCount,
            'expiry_days' => $this->expiryDays,
        ];
    }
}
