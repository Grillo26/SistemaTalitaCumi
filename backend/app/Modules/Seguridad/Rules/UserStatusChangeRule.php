<?php

namespace App\Modules\Seguridad\Rules;

use App\Modules\Seguridad\Exceptions\UserStatusReasonRequiredException;
use App\Modules\Seguridad\Exceptions\UserStatusUnchangedException;

/**
 * Transiciones de estado de usuario (02/RF-U09 / 02/RN-10).
 *
 * El motivo es obligatorio al desactivar; al activar es opcional.
 */
final class UserStatusChangeRule
{
    public function reason(?string $reason, bool $targetActive): ?string
    {
        $reason = trim((string) $reason);

        if (! $targetActive && $reason === '') {
            throw new UserStatusReasonRequiredException;
        }

        return $reason === '' ? null : $reason;
    }

    public function assertDifferent(bool $current, bool $target): void
    {
        if ($current === $target) {
            throw new UserStatusUnchangedException($target);
        }
    }
}
