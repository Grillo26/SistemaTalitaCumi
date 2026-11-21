<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Actions\Concerns\ChangesBranchStatus;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Models\Branch;

/**
 * Desactivar sucursal (RF-007 / RN-005).
 */
final class DeactivateBranchAction
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
        return $this->changeStatus($branchId, false, $reason, $expectedVersion, $actorId, $hasGlobalScope);
    }
}
