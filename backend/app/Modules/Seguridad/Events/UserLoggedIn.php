<?php

namespace App\Modules\Seguridad\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Inicio de sesión exitoso (RF-A01, RF-A12).
 */
final class UserLoggedIn
{
    use Dispatchable;

    /**
     * @param  list<int>  $replacedSessions
     */
    public function __construct(
        public readonly int $userId,
        public readonly ?int $tokenId,
        public readonly ?string $expiresAt,
        public readonly ?string $previousLastAccess,
        public readonly array $replacedSessions,
        public readonly ?string $ip,
        public readonly ?string $userAgent,
    ) {}
}
