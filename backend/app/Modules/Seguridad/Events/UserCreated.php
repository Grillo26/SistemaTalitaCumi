<?php

namespace App\Modules\Seguridad\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Alta de usuario (02/RF-U01, 02/RF-U12).
 */
final class UserCreated
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function __construct(
        public readonly int $userId,
        public readonly array $snapshot,
        public readonly int $actorId,
        public readonly ?string $ip,
    ) {}
}
