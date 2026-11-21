<?php

namespace App\Modules\Seguridad\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Desbloqueo manual de cuenta (02/RF-A06 / 02/RN-13).
 */
final class UserUnlocked
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
        public readonly string $reason,
        public readonly int $actorId,
        public readonly ?string $ip,
    ) {}
}
