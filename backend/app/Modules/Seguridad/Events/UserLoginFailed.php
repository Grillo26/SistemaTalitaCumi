<?php

namespace App\Modules\Seguridad\Events;

use App\Modules\Seguridad\Enums\AuditResult;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Intento de inicio de sesión fallido o denegado (RF-A05, RF-A11, RF-A12).
 */
final class UserLoginFailed
{
    use Dispatchable;

    public function __construct(
        public readonly ?int $userId,
        public readonly string $identifier,
        public readonly string $reason,
        public readonly AuditResult $result,
        public readonly ?string $ip,
        public readonly ?string $userAgent,
    ) {}
}
