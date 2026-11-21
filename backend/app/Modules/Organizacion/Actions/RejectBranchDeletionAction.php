<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Organizacion\Exceptions\BranchNotFoundException;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Organizacion\Rules\BranchDeletionRule;

/**
 * Rechazo explícito de eliminación física (RF-008 / RN-004 / CA-007).
 *
 * No se elimina ninguna sucursal: se conserva el historial y se ofrece
 * desactivarla. El chequeo real de datos asociados se agregará cuando
 * existan los módulos consumidores.
 */
final class RejectBranchDeletionAction
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    public function __invoke(int $branchId, ?int $actorId, bool $hasGlobalScope): never
    {
        $branch = Branch::query()->find($branchId);

        if ($branch === null) {
            throw new BranchNotFoundException($branchId);
        }

        if (! $hasGlobalScope && ($actorId === null || ! $this->access->puedeAcceder($actorId, $branchId))) {
            throw new BranchAccessDeniedException;
        }

        BranchDeletionRule::reject($branch->nombre);
    }
}
