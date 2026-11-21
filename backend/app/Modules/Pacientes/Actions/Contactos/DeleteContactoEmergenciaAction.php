<?php

namespace App\Modules\Pacientes\Actions\Contactos;

use App\Models\User;
use App\Modules\Pacientes\Events\Contactos\ContactoEmergenciaDeleted;
use App\Modules\Pacientes\Exceptions\ContactoEmergenciaNotFoundException;
use App\Modules\Pacientes\Models\ContactoEmergencia;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Support\ContactoEmergenciaSnapshot;
use App\Modules\Pacientes\Support\PacienteAccessScope;
use App\Modules\Pacientes\Support\PacienteBranchResolver;
use Illuminate\Support\Facades\DB;

/**
 * Eliminar contacto de emergencia (04.02/RF-012).
 */
final class DeleteContactoEmergenciaAction
{
    public function __construct(
        private readonly PacienteAccessScope $scope,
        private readonly PacienteBranchResolver $branches,
    ) {}

    public function __invoke(
        Paciente $paciente,
        ContactoEmergencia $contacto,
        User $actor,
        ?string $ip = null,
    ): void {
        $this->scope->assertCanAccess($actor, $paciente);

        DB::transaction(function () use ($paciente, $contacto, $actor, $ip): void {
            $current = ContactoEmergencia::query()
                ->whereKey($contacto->id)
                ->where('paciente_id', $paciente->id)
                ->lockForUpdate()
                ->first();

            if ($current === null) {
                throw new ContactoEmergenciaNotFoundException((int) $contacto->id);
            }

            $before = ContactoEmergenciaSnapshot::from($current);

            $current->delete();

            event(new ContactoEmergenciaDeleted(
                contactoId: (int) $before['id'],
                pacienteId: (int) $paciente->id,
                snapshot: $before,
                branchIds: $this->branches->for($paciente),
                actorId: $actor->id,
                ip: $ip,
            ));
        });
    }
}
