<?php

namespace App\Modules\Pacientes\Queries\Tutores;

use App\Models\User;
use App\Modules\Pacientes\Exceptions\TutorNotFoundException;
use App\Modules\Pacientes\Models\Tutor;
use App\Modules\Pacientes\Support\PacientesPermission;
use App\Modules\Pacientes\Support\TutorAccessScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ficha de tutor con pacientes visibles (04.02/RF-008).
 */
final class GetTutorQuery
{
    public function __construct(private readonly TutorAccessScope $scope) {}

    public function __invoke(int $tutorId, User $actor): Tutor
    {
        $branchIds = $this->scope->accessibleBranchIds($actor);
        $canViewPatients = $actor->can(PacientesPermission::PATIENT_VIEW);

        $tutor = Tutor::query()
            ->with([
                'parentescoCatalogo',
                'sucursalRegistro',
                'relacionesPaciente' => function ($query) use ($branchIds, $canViewPatients): void {
                    $query
                        ->with(['paciente', 'parentesco'])
                        ->orderByDesc('es_principal')
                        ->orderBy('tutor_id');

                    if (! $canViewPatients) {
                        $query->whereRaw('1 = 0');

                        return;
                    }

                    if ($branchIds === []) {
                        $query->whereRaw('1 = 0');

                        return;
                    }

                    if ($branchIds !== null) {
                        $query->whereHas(
                            'paciente',
                            static fn (Builder $pacientes) => $pacientes
                                ->whereIn('sucursal_registro_id', $branchIds),
                        );
                    }
                },
            ])
            ->find($tutorId);

        if ($tutor === null) {
            throw new TutorNotFoundException($tutorId);
        }

        $this->scope->assertCanAccess($actor, $tutor);

        return $tutor;
    }
}
