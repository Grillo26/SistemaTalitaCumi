<?php

namespace App\Modules\Organizacion\Queries;

use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Organizacion\Exceptions\BranchNotFoundException;
use App\Modules\Organizacion\Models\Branch;

/**
 * Ficha de sucursal (RF-005).
 */
final class GetBranchQuery
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    public function __invoke(int $branchId, int $actorId, bool $hasGlobalScope): Branch
    {
        $branch = Branch::query()->find($branchId);

        if ($branch === null) {
            throw new BranchNotFoundException($branchId);
        }

        if (! $hasGlobalScope && ! $this->access->puedeAcceder($actorId, $branchId)) {
            throw new BranchAccessDeniedException;
        }

        return $branch;
    }
}
