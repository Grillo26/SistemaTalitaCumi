<?php

namespace App\Modules\Seguridad\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Edición de usuario, rol o sucursales (02/RF-U08, 02/RF-U12, 02/RF-R12).
 */
final class UserUpdated
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  list<string>  $changedFields
     */
    public function __construct(
        public readonly int $userId,
        public readonly array $before,
        public readonly array $after,
        public readonly array $changedFields,
        public readonly bool $roleChanged,
        public readonly int $sessionsInvalidated,
        public readonly int $actorId,
        public readonly ?string $ip,
    ) {}
}
