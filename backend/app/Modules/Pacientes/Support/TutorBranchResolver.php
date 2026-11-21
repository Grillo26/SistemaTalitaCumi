<?php

namespace App\Modules\Pacientes\Support;

use App\Models\User;
use App\Modules\Pacientes\Models\Tutor;

/**
 * Sucursales visibles de un tutor para bitácora y consulta (04.02/RN-010).
 */
final class TutorBranchResolver
{
    public function __construct(private readonly TutorAccessScope $scope) {}

    /**
     * Home branch + sucursales de los pacientes asociados.
     *
     * @return list<int>
     */
    public function for(Tutor $tutor): array
    {
        $branchIds = [];

        if ($tutor->sucursal_registro_id !== null) {
            $branchIds[] = (int) $tutor->sucursal_registro_id;
        }

        $patientBranchIds = $tutor->relacionesPaciente()
            ->join('paciente', 'paciente.id', '=', 'paciente_tutor.paciente_id')
            ->where('paciente_tutor.tutor_id', $tutor->id)
            ->orderBy('paciente.sucursal_registro_id')
            ->pluck('paciente.sucursal_registro_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        return array_values(array_unique(array_merge($branchIds, $patientBranchIds)));
    }

    /**
     * Sucursal que otorga acceso para la fila CONSULTAR.
     */
    public function forConsultation(User $actor, Tutor $tutor): ?int
    {
        $branchIds = $this->for($tutor);
        $actorBranchIds = $this->scope->accessibleBranchIds($actor);

        if ($actorBranchIds === null) {
            return $branchIds[0] ?? null;
        }

        if ($actorBranchIds === []) {
            return null;
        }

        $accessible = array_values(array_intersect($branchIds, $actorBranchIds));

        return $accessible[0] ?? null;
    }
}
