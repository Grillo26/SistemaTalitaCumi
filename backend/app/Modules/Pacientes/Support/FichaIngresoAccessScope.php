<?php

namespace App\Modules\Pacientes\Support;

use App\Models\User;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Pacientes\Exceptions\FichaIngresoNotFoundException;
use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Models\Paciente;

/**
 * Alcance de la ficha de ingreso (04.03/RF-013, 04.03/RN-008).
 *
 * Exige paciente accesible y sucursal de ingreso accesible. Fail-closed.
 */
final class FichaIngresoAccessScope
{
    public function __construct(
        private readonly PacienteAccessScope $pacientes,
        private readonly BranchDirectoryContract $branches,
    ) {}

    /**
     * @return list<int>|null NULL = alcance global
     */
    public function accessibleBranchIds(User $actor): ?array
    {
        return $this->pacientes->accessibleBranchIds($actor);
    }

    public function canAccess(User $actor, FichaIngreso $ficha): bool
    {
        if (! $this->pacientes->canAccess($actor, $ficha->paciente)) {
            return false;
        }

        $branchIds = $this->accessibleBranchIds($actor);

        if ($branchIds === null) {
            return true;
        }

        return in_array((int) $ficha->sucursal_ingreso_id, $branchIds, true);
    }

    public function assertCanAccess(User $actor, FichaIngreso $ficha): void
    {
        if (! $this->canAccess($actor, $ficha)) {
            throw new FichaIngresoNotFoundException((int) $ficha->id);
        }
    }

    public function assertCanCreate(User $actor, Paciente $paciente, ?int $sucursalIngresoId): int
    {
        $this->pacientes->assertCanAccess($actor, $paciente);

        $sucursalIngresoId ??= (int) $paciente->sucursal_registro_id;
        $branchIds = $this->accessibleBranchIds($actor);

        if ($branchIds !== null && ! in_array($sucursalIngresoId, $branchIds, true)) {
            throw new BranchAccessDeniedException;
        }

        $this->branches->asegurarActiva($sucursalIngresoId);

        return $sucursalIngresoId;
    }
}
