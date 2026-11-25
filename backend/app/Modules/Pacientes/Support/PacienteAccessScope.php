<?php

namespace App\Modules\Pacientes\Support;

use App\Models\User;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Pacientes\Exceptions\PacienteNotFoundException;
use App\Modules\Pacientes\Models\Paciente;
use Illuminate\Database\Eloquent\Builder;

/**
 * Alcance por sucursal del módulo 04.01 (04.01/RN-010, 04.01/RF-014).
 *
 * El paciente es global. La visibilidad inicial se ancla en
 * `sucursal_registro_id`; cuando exista 06 se sumarán las inscripciones
 * vigentes. Fail-closed: sin sucursales no se devuelve ninguna fila.
 */
final class PacienteAccessScope
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

    public function apply(Builder $query, User $actor, ?int $sucursalId = null): Builder
    {
        $branchIds = $this->accessibleBranchIds($actor);

        if ($branchIds === null) {
            return $sucursalId === null
                ? $query
                : $query->sucursalRegistro($sucursalId);
        }

        if ($branchIds === []) {
            return $query->whereRaw('1 = 0');
        }

        if ($sucursalId !== null) {
            if (! in_array($sucursalId, $branchIds, true)) {
                return $query->whereRaw('1 = 0');
            }

            return $query->sucursalRegistro($sucursalId);
        }

        return $query->whereIn('sucursal_registro_id', $branchIds);
    }

    public function canAccess(User $actor, Paciente $paciente): bool
    {
        $branchIds = $this->accessibleBranchIds($actor);

        if ($branchIds === null) {
            return true;
        }

        return in_array((int) $paciente->sucursal_registro_id, $branchIds, true);
    }

    /**
     * Un paciente fuera de alcance se presenta como inexistente para no
     * revelar datos de menores de otras sucursales.
     */
    public function assertCanAccess(User $actor, Paciente $paciente): void
    {
        if (! $this->canAccess($actor, $paciente)) {
            throw new PacienteNotFoundException($paciente->id);
        }
    }

    public function assertBranchAllowed(User $actor, int $branchId): void
    {
        $branchIds = $this->accessibleBranchIds($actor);

        if ($branchIds !== null && ! in_array($branchId, $branchIds, true)) {
            throw new BranchAccessDeniedException;
        }

        $this->branches->asegurarActiva($branchId);
    }

    public function defaultBranchId(User $actor): ?int
    {
        $branchIds = $this->accessibleBranchIds($actor);

        return $branchIds !== null && count($branchIds) === 1 ? $branchIds[0] : null;
    }

    /**
     * @return list<int>
     */
    public function branchIdsFor(Paciente $paciente): array
    {
        return [(int) $paciente->sucursal_registro_id];
    }
}
