<?php

namespace App\Modules\Seguridad\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Reseteo de contraseña por Superadmin (02/RF-U10, 02/RF-A04 / 02/RN-07).
 */
final class UserPasswordReset
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
        public readonly int $sessionsInvalidated,
        public readonly int $actorId,
        public readonly ?string $ip,
    ) {}
}
