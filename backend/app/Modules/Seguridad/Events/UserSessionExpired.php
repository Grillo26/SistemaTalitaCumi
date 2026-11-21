<?php

namespace App\Modules\Seguridad\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Sesión expirada por inactividad (02/RF-A07, 02/RF-A12).
 */
final class UserSessionExpired
{
    use Dispatchable;

    public function __construct(
        public readonly int $userId,
        public readonly ?int $tokenId,
        public readonly ?string $ip,
    ) {}
}
