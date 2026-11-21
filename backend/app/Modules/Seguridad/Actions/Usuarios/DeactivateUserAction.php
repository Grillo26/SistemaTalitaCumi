<?php

namespace App\Modules\Seguridad\Actions\Usuarios;

use App\Models\User;
use App\Modules\Seguridad\Actions\Usuarios\Concerns\ChangesUserStatus;
use App\Modules\Seguridad\Rules\LastSuperadminRule;
use App\Modules\Seguridad\Support\SuperadminGuard;
use App\Modules\Seguridad\Support\UserAccessScope;

/**
 * Desactivar usuario con motivo obligatorio (02/RF-U09 / 02/RN-10).
 */
final class DeactivateUserAction
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
        return $this->changeStatus($target, false, $reason, $expectedVersion, $actor, $ip);
    }
}
