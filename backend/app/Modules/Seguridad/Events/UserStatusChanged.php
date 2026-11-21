<?php

namespace App\Modules\Seguridad\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Activación o desactivación de usuario (02/RF-U09 / 02/RN-05, 02/RN-10).
 */
final class UserStatusChanged
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function __construct(
        public readonly int $userId,
        public readonly array $before,
        public readonly array $after,
        public readonly bool $active,
        public readonly ?string $reason,
        public readonly int $sessionsInvalidated,
        public readonly int $actorId,
        public readonly ?string $ip,
    ) {}
}
