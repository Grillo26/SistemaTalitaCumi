<?php

namespace App\Modules\Profesionales\Support;

use App\Models\User;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Profesionales\Models\Profesional;
use Illuminate\Database\Eloquent\Builder;

/**
 * Alcance por sucursal del directorio de profesionales (03/RN-18).
 *
 * Sin alcance global, un usuario solo ve profesionales con una relación
 * activa con alguna de sus sucursales accesibles. Fail-closed: sin
 * sucursales la consulta no devuelve filas. Nunca “todas” como atajo.
 */
final class ProfesionalAccessScope
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    /**
     * @return list<int>|null NULL = alcance global
     */
    public function accessibleBranchIds(User $actor): ?array
    {
        if ($actor->can(ProfesionalesPermission::GLOBAL_SCOPE)) {
            return null;
        }

        return $this->access->accesibles($actor->id);
    }

    public function apply(Builder $query, User $actor, ?int $sucursalId = null): Builder
    {
        $branchIds = $this->accessibleBranchIds($actor);

        if ($branchIds === null) {
            return $sucursalId === null
                ? $query
                : $query->sucursal($sucursalId);
        }

        if ($branchIds === []) {
            return $query->whereRaw('1 = 0');
        }

        if ($sucursalId !== null) {
            if (! in_array($sucursalId, $branchIds, true)) {
                return $query->whereRaw('1 = 0');
            }

            return $query->sucursal($sucursalId);
        }

        return $query->whereHas(
            'relacionesSucursal',
            static fn (Builder $relaciones) => $relaciones
                ->where('activo', true)
                ->whereIn('sucursal_id', $branchIds),
        );
    }

    public function canAccess(User $actor, Profesional $profesional): bool
    {
        $branchIds = $this->accessibleBranchIds($actor);

        if ($branchIds === null) {
            return true;
        }

        if ($branchIds === []) {
            return false;
        }

        return $profesional->relacionesSucursal()
            ->where('activo', true)
            ->whereIn('sucursal_id', $branchIds)
            ->exists();
    }

    public function assertCanAccess(User $actor, Profesional $profesional): void
    {
        if (! $this->canAccess($actor, $profesional)) {
            throw new BranchAccessDeniedException;
        }
    }
}
