<?php

namespace App\Modules\Pacientes\Support;

use App\Models\User;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Pacientes\Exceptions\TutorInactiveException;
use App\Modules\Pacientes\Exceptions\TutorNotFoundException;
use App\Modules\Pacientes\Exceptions\TutorSucursalRequiredException;
use App\Modules\Pacientes\Models\Tutor;
use Illuminate\Database\Eloquent\Builder;

/**
 * Visibilidad de tutores (04.02/RF-015, 04.02/RN-010).
 *
 * Un tutor es visible por su sucursal de registro (home branch) o por una
 * sucursal de un paciente asociado. Fail-closed: un tutor sin pacientes y sin
 * home branch solo lo ve el alcance global; nunca “todas las sucursales”.
 */
final class TutorAccessScope
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly BranchDirectoryContract $branches,
    ) {}

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

    public function assertCanCreate(User $actor, ?int $sucursalId): int
    {
        $branchIds = $this->accessibleBranchIds($actor);

        if ($branchIds === []) {
            throw new BranchAccessDeniedException;
        }

        if ($sucursalId === null) {
            if ($branchIds === null || count($branchIds) !== 1) {
                throw new TutorSucursalRequiredException;
            }

            $sucursalId = $branchIds[0];
        }

        if ($branchIds !== null && ! in_array($sucursalId, $branchIds, true)) {
            throw new BranchAccessDeniedException;
        }

        $this->branches->asegurarActiva($sucursalId);

        return $sucursalId;
    }

    public function apply(Builder $query, User $actor, ?int $sucursalId = null): Builder
    {
        $branchIds = $this->accessibleBranchIds($actor);

        if ($branchIds === null) {
            return $sucursalId === null
                ? $query
                : $query->where('sucursal_registro_id', $sucursalId);
        }

        if ($branchIds === []) {
            return $query->whereRaw('1 = 0');
        }

        if ($sucursalId !== null) {
            if (! in_array($sucursalId, $branchIds, true)) {
                return $query->whereRaw('1 = 0');
            }

            // El filtro público es por sucursal de registro (home branch).
            return $query->where('sucursal_registro_id', $sucursalId);
        }

        return $query->where(function (Builder $query) use ($branchIds): void {
            $query
                ->whereIn('sucursal_registro_id', $branchIds)
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

        if ($tutor->sucursal_registro_id !== null
            && in_array((int) $tutor->sucursal_registro_id, $branchIds, true)) {
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
