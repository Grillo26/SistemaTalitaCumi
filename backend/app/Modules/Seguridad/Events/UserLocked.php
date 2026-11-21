<?php

namespace App\Modules\Seguridad\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Cuenta bloqueada por intentos fallidos (RF-A05 / RN-13).
 */
final class UserLocked
{
    use Dispatchable;

    public function __construct(
        public readonly int $userId,
        public readonly int $failedAttempts,
        public readonly ?string $blockedUntil,
        public readonly ?string $ip,
    ) {}
}
