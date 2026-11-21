<?php

namespace App\Modules\Seguridad\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Cierre remoto de todas las sesiones (02/RF-A09 / 02/RN-10, 02/RN-14).
 */
final class UserSessionsClosed
{
    use Dispatchable;

    public function __construct(
        public readonly int $userId,
        public readonly int $sessionsClosed,
        public readonly string $reason,
        public readonly int $actorId,
        public readonly ?string $ip,
    ) {}
}
