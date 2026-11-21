<?php

namespace App\Modules\Seguridad\Support;

use App\Models\User;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Support\BranchPermission;
use App\Modules\Seguridad\Exceptions\AuditAccessDeniedException;
use App\Modules\Seguridad\Models\BitacoraEntry;
use Illuminate\Database\Eloquent\Builder;

/**
 * Scoping de bitácora por sucursal (02/RF-B06, 02/RF-B14).
 *
 * Los eventos globales de seguridad (sucursal_id NULL) solo son visibles
 * para alcance global; Administración ve eventos de sus sucursales.
 */
final class AuditScope
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

        return $query->whereIn('sucursal_id', $branchIds);
    }

    public function canView(User $actor, BitacoraEntry $entry): bool
    {
        if ($actor->can(BranchPermission::GLOBAL_SCOPE)) {
            return true;
        }

        if ($entry->sucursal_id === null) {
            return false;
        }

        return in_array(
            (int) $entry->sucursal_id,
            $this->access->accesibles($actor->id),
            true,
        );
    }

    public function assertCanView(User $actor, BitacoraEntry $entry): void
    {
        if (! $this->canView($actor, $entry)) {
            throw new AuditAccessDeniedException;
        }
    }
}
