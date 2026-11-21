<?php

namespace App\Modules\Seguridad\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Cierre de sesión local (RF-A08, RF-A12).
 */
final class UserLoggedOut
{
    use Dispatchable;

    public function __construct(
        public readonly int $userId,
        public readonly ?int $tokenId,
        public readonly ?string $ip,
    ) {}
}
