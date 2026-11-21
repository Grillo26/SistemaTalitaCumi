<?php

namespace App\Modules\Seguridad\Actions\Usuarios;

use App\Models\User;
use App\Modules\Seguridad\Actions\Usuarios\Concerns\ChangesUserStatus;
use App\Modules\Seguridad\Rules\LastSuperadminRule;
use App\Modules\Seguridad\Support\SuperadminGuard;
use App\Modules\Seguridad\Support\UserAccessScope;

/**
 * Activar usuario (02/RF-U09).
 */
final class ActivateUserAction
{
    use ChangesUserStatus;

    public function __construct(
        private readonly UserAccessScope $scope,
        private readonly SuperadminGuard $superadminGuard,
        private readonly LastSuperadminRule $lastSuperadminRule,
    ) {}

    public function __invoke(
        User $target,
        ?string $reason,
        int $expectedVersion,
        User $actor,
        ?string $ip,
    ): User {
        return $this->changeStatus($target, true, $reason, $expectedVersion, $actor, $ip);
    }
}
