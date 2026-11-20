<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Actions\Concerns\ChangesBranchStatus;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Models\Branch;

/**
 * Activar sucursal (RF-007 / RN-005).
 */
final class ActivateBranchAction
{
    use ChangesBranchStatus;

    public function __construct(private readonly UserBranchAccessContract $access) {}

    public function __invoke(
        int $branchId,
        string $reason,
        int $expectedVersion,
        ?int $actorId,
        bool $hasGlobalScope,
    ): Branch {
        return $this->changeStatus($branchId, true, $reason, $expectedVersion, $actorId, $hasGlobalScope);
    }
}
