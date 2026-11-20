<?php

namespace App\Modules\Organizacion\Actions\Concerns;

use App\Modules\Organizacion\Events\BranchActivated;
use App\Modules\Organizacion\Events\BranchDeactivated;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Organizacion\Exceptions\BranchConcurrencyException;
use App\Modules\Organizacion\Exceptions\BranchNotFoundException;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Organizacion\Rules\BranchStatusChangeRule;
use App\Modules\Organizacion\Support\BranchSnapshot;
use Illuminate\Support\Facades\DB;

trait ChangesBranchStatus
{
    protected function changeStatus(
        int $branchId,
        bool $targetStatus,
        string $reason,
        int $expectedVersion,
        ?int $actorId,
        bool $hasGlobalScope,
    ): Branch {
        if (! $hasGlobalScope && ($actorId === null || ! $this->access->puedeAcceder($actorId, $branchId))) {
            throw new BranchAccessDeniedException;
        }

        $rule = new BranchStatusChangeRule;
        $reason = $rule->reason($reason);

        return DB::transaction(function () use ($rule, $branchId, $targetStatus, $reason, $expectedVersion, $actorId): Branch {
            $branch = Branch::query()
                ->whereKey($branchId)
                ->lockForUpdate()
                ->first();

            if ($branch === null) {
                throw new BranchNotFoundException($branchId);
            }

            if ($branch->version !== $expectedVersion) {
                throw new BranchConcurrencyException;
            }

            $rule->assertDifferent($branch->activo, $targetStatus);

            $before = BranchSnapshot::from($branch);

            $branch->activo = $targetStatus;
            $branch->version = $branch->version + 1;
            $branch->save();
            $branch->refresh();

            $after = BranchSnapshot::from($branch);

            event($targetStatus
                ? new BranchActivated($branch->id, $before, $after, $reason, $actorId)
                : new BranchDeactivated($branch->id, $before, $after, $reason, $actorId));

            return $branch;
        });
    }
}
