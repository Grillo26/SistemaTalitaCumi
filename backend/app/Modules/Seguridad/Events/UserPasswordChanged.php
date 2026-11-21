<?php

namespace App\Modules\Seguridad\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Cambio de contraseña por el propio usuario (RF-A03, RF-A12).
 */
final class UserPasswordChanged
{
    use Dispatchable;

    public function __construct(
        public readonly int $userId,
        public readonly int $changedBy,
        public readonly int $revokedSessions,
        public readonly ?string $ip,
    ) {}
}
