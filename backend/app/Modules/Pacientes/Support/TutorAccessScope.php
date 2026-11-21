<?php

namespace App\Modules\Pacientes\Support;

use App\Models\User;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Pacientes\Exceptions\TutorInactiveException;
use App\Modules\Pacientes\Exceptions\TutorNotFoundException;
use App\Modules\Pacientes\Models\Tutor;
use Illuminate\Database\Eloquent\Builder;

/**
 * Visibilidad de tutores (04.02/RF-015, 04.02/RN-010).
 *
 * Un tutor es global. Sin alcance global se ven tutores asociados a pacientes
 * de las sucursales accesibles, más tutores todavía sin pacientes (para poder
 * asociarlos). Fail-closed: sin sucursales no se ven tutores asociados.
 */
final class TutorAccessScope
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    /**
     * @return list<int>|null NULL = alcance global
     */
    public function accessibleBranchIds(User $actor): ?array
    {
        if ($actor->can(PacientesPermission::GLOBAL_SCOPE)) {
            return null;
        }

        return $this->access->accesibles($actor->id);
    }

    public function assertCanCreate(User $actor): void
    {
        if ($this->accessibleBranchIds($actor) === []) {
            throw new BranchAccessDeniedException;
        }
    }

    public function apply(Builder $query, User $actor): Builder
    {
        $branchIds = $this->accessibleBranchIds($actor);

        if ($branchIds === null) {
            return $query;
        }

        if ($branchIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $query) use ($branchIds): void {
            $query
                ->whereDoesntHave('relacionesPaciente')
                ->orWhereHas(
                    'relacionesPaciente.paciente',
                    static fn (Builder $pacientes) => $pacientes
                        ->whereIn('sucursal_registro_id', $branchIds),
                );
        });
    }

    public function canAccess(User $actor, Tutor $tutor): bool
    {
        $branchIds = $this->accessibleBranchIds($actor);

        if ($branchIds === null) {
            return true;
        }

        if ($branchIds === []) {
            return false;
        }

        if (! $tutor->relacionesPaciente()->exists()) {
            return true;
        }

        return $tutor->relacionesPaciente()
            ->whereHas(
                'paciente',
                static fn (Builder $pacientes) => $pacientes
                    ->whereIn('sucursal_registro_id', $branchIds),
            )
            ->exists();
    }

    public function assertCanAccess(User $actor, Tutor $tutor): void
    {
        if (! $this->canAccess($actor, $tutor)) {
            throw new TutorNotFoundException((int) $tutor->id);
        }
    }

    public function canAssociate(User $actor, Tutor $tutor): bool
    {
        return (bool) $tutor->activo && $this->canAccess($actor, $tutor);
    }

    public function assertCanAssociate(User $actor, Tutor $tutor): void
    {
        $this->assertCanAccess($actor, $tutor);

        if (! $tutor->activo) {
            throw new TutorInactiveException;
        }
    }
}
