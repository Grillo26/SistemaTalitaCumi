<?php

namespace App\Modules\Seguridad\Support;

use App\Models\User;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Organizacion\Support\BranchPermission;
use App\Modules\Seguridad\Exceptions\UserAccessDeniedException;
use App\Modules\Seguridad\Exceptions\UserBranchInvalidException;
use App\Modules\Seguridad\Exceptions\UserBranchRequiredException;
use Illuminate\Database\Eloquent\Builder;

/**
 * Scoping de usuarios por sucursal (02/RF-R07, 02/RF-R09 / 02/RN-03).
 */
final class UserAccessScope
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    public function apply(Builder $query, User $actor): Builder
    {
        if ($actor->can(BranchPermission::GLOBAL_SCOPE)) {
            return $query;
        }

        $branchIds = $this->access->accesibles($actor->id);

        if ($branchIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'branches',
            static fn (Builder $branches) => $branches->whereIn('sucursal.id', $branchIds),
        );
    }

    public function canAccess(User $actor, User $target): bool
    {
        if ($actor->can(BranchPermission::GLOBAL_SCOPE)) {
            return true;
        }

        $branchIds = $this->access->accesibles($actor->id);

        if ($branchIds === []) {
            return false;
        }

        return $target->branches()
            ->whereIn('sucursal.id', $branchIds)
            ->exists();
    }

    public function assertCanAccess(User $actor, User $target): void
    {
        if (! $this->canAccess($actor, $target)) {
            throw new UserAccessDeniedException;
        }
    }

    /**
     * @param  list<int>  $branchIds
     */
    public function assertCanAssignBranches(User $actor, array $branchIds): void
    {
        $branchIds = array_values(array_unique(array_map('intval', $branchIds)));

        if ($branchIds === []) {
            throw new UserBranchRequiredException;
        }

        $activeCount = Branch::query()
            ->whereIn('id', $branchIds)
            ->where('activo', true)
            ->count();

        if ($activeCount !== count($branchIds)) {
            throw new UserBranchInvalidException;
        }

        if ($actor->can(BranchPermission::GLOBAL_SCOPE)) {
            return;
        }

        $accessible = $this->access->accesibles($actor->id);

        foreach ($branchIds as $branchId) {
            if (! in_array($branchId, $accessible, true)) {
                throw new BranchAccessDeniedException;
            }
        }
    }
}
