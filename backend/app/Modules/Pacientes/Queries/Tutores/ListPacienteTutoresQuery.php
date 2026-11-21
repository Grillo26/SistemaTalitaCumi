<?php

namespace App\Modules\Pacientes\Queries\Tutores;

use App\Models\User;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Models\PacienteTutor;
use App\Modules\Pacientes\Support\PacienteAccessScope;
use Illuminate\Database\Eloquent\Collection;

/**
 * Tutores asociados a un paciente (04.02/RF-004).
 */
final class ListPacienteTutoresQuery
{
    public function __construct(private readonly PacienteAccessScope $scope) {}

    /**
     * @return Collection<int, PacienteTutor>
     */
    public function __invoke(Paciente $paciente, User $actor): Collection
    {
        $this->scope->assertCanAccess($actor, $paciente);

        return PacienteTutor::query()
            ->where('paciente_id', $paciente->id)
            ->with(['tutor.parentescoCatalogo', 'tutor.sucursalRegistro', 'parentesco'])
            ->orderByDesc('es_principal')
            ->orderBy('tutor_id')
            ->get();
    }
}
