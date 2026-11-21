<?php

namespace App\Modules\Pacientes\Queries\Tutores;

use App\Models\User;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Models\PacienteTutor;
use App\Modules\Pacientes\Support\PacienteAccessScope;

/**
 * Tutores asociados a un paciente (04.01/RF-006, 04.02/RF-004).
 */
final class ListPacienteTutoresQuery
{
    public function __construct(private readonly PacienteAccessScope $scope) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function __invoke(Paciente $paciente, User $actor): array
    {
        $this->scope->assertCanAccess($actor, $paciente);

        return PacienteTutor::query()
            ->where('paciente_id', $paciente->id)
            ->with('tutor')
            ->orderByDesc('es_principal')
            ->orderBy('tutor_id')
            ->get()
            ->filter(static fn (PacienteTutor $relacion): bool => $relacion->tutor !== null)
            ->map(static fn (PacienteTutor $relacion): array => [
                'tutor_id' => (int) $relacion->tutor_id,
                'es_principal' => (bool) $relacion->es_principal,
                'nombre_completo' => $relacion->tutor->nombre_completo,
                'ci' => $relacion->tutor->ci,
                'telefono' => $relacion->tutor->telefono,
                'telefono_alternativo' => $relacion->tutor->telefono_alternativo,
                'email' => $relacion->tutor->email,
                'direccion' => $relacion->tutor->direccion,
                'parentesco' => $relacion->tutor->parentesco,
                'activo' => (bool) $relacion->tutor->activo,
            ])
            ->values()
            ->all();
    }
}
