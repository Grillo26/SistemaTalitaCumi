<?php

namespace App\Modules\CatalogoServicios\Support;

use App\Models\User;
use App\Modules\CatalogoServicios\Exceptions\TarifaNotFoundException;
use App\Modules\CatalogoServicios\Models\Tarifa;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Organizacion\Support\BranchPermission;
use Illuminate\Database\Eloquent\Builder;

/**
 * Alcance por sucursal de tarifas (RF-T17 / RN-T11).
 *
 * Las tarifas globales son visibles para usuarios con sucursales asignadas,
 * porque aplican a todas. Solo el alcance global puede administrarlas.
 * Fail-closed: sin sucursales no se devuelven filas.
 */
final class TarifaAccessScope
{
    /** @var array<int, list<int>> */
    private array $cache = [];

    public function __construct(private readonly UserBranchAccessContract $access) {}

    /**
     * @return list<int>|null NULL = alcance global
     */
    public function accessibleBranchIds(User $actor): ?array
    {
        if ($actor->can(BranchPermission::GLOBAL_SCOPE)) {
            return null;
        }

        return $this->cache[$actor->id] ??= $this->access->accesibles($actor->id);
    }

    public function apply(
        Builder $query,
        User $actor,
        ?int $sucursalId = null,
        bool $soloGlobal = false,
    ): Builder {
        $branchIds = $this->accessibleBranchIds($actor);

        if ($soloGlobal) {
            if ($branchIds !== null && $branchIds === []) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereNull('sucursal_id');
        }

        if ($branchIds === null) {
            return $sucursalId === null
                ? $query
                : $query->where(static function (Builder $query) use ($sucursalId): void {
                    $query->whereNull('sucursal_id')->orWhere('sucursal_id', $sucursalId);
                });
        }

        if ($branchIds === []) {
            return $query->whereRaw('1 = 0');
        }

        if ($sucursalId !== null) {
            if (! in_array($sucursalId, $branchIds, true)) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where(static function (Builder $query) use ($sucursalId): void {
                $query->whereNull('sucursal_id')->orWhere('sucursal_id', $sucursalId);
            });
        }

        return $query->where(static function (Builder $query) use ($branchIds): void {
            $query->whereNull('sucursal_id')->orWhereIn('sucursal_id', $branchIds);
        });
    }

    public function canView(User $actor, Tarifa $tarifa): bool
    {
        $branchIds = $this->accessibleBranchIds($actor);

        if ($branchIds === null) {
            return true;
        }

        if ($branchIds === []) {
            return false;
        }

        return $tarifa->sucursal_id === null
            || in_array($tarifa->sucursal_id, $branchIds, true);
    }

    public function assertCanView(User $actor, Tarifa $tarifa): void
    {
        if (! $this->canView($actor, $tarifa)) {
            throw new TarifaNotFoundException($tarifa->id);
        }
    }

    public function canManage(User $actor, Tarifa $tarifa): bool
    {
        if (! $actor->can(CatalogoServiciosPermission::TARIFA_EDIT)) {
            return false;
        }

        $branchIds = $this->accessibleBranchIds($actor);

        if ($branchIds === null) {
            return true;
        }

        if ($tarifa->sucursal_id === null) {
            return false;
        }

        return in_array($tarifa->sucursal_id, $branchIds, true);
    }

    public function assertCanManage(User $actor, Tarifa $tarifa): void
    {
        if (! $this->canManage($actor, $tarifa)) {
            throw new BranchAccessDeniedException;
        }
    }

    public function assertCanCreateForBranch(User $actor, ?int $sucursalId): void
    {
        if ($sucursalId === null) {
            if (! $actor->can(BranchPermission::GLOBAL_SCOPE)) {
                throw new BranchAccessDeniedException;
            }

            return;
        }

        $this->assertBranchAllowed($actor, $sucursalId);
    }

    public function assertBranchAllowed(User $actor, int $sucursalId): void
    {
        $branchIds = $this->accessibleBranchIds($actor);

        if ($branchIds === null) {
            return;
        }

        if (! in_array($sucursalId, $branchIds, true)) {
            throw new BranchAccessDeniedException;
        }
    }
}
