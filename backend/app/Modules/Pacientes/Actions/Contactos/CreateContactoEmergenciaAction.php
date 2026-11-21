<?php

namespace App\Modules\Pacientes\Actions\Contactos;

use App\Models\User;
use App\Modules\Pacientes\Events\Contactos\ContactoEmergenciaCreated;
use App\Modules\Pacientes\Exceptions\ContactoEmergenciaPhoneInvalidException;
use App\Modules\Pacientes\Models\ContactoEmergencia;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Rules\TutorIdentityRule;
use App\Modules\Pacientes\Rules\TutorPhoneRule;
use App\Modules\Pacientes\Support\ContactoEmergenciaSnapshot;
use App\Modules\Pacientes\Support\PacienteAccessScope;
use App\Modules\Pacientes\Support\PacienteBranchResolver;
use App\Modules\Pacientes\Support\ParentescoResolver;
use Illuminate\Support\Facades\DB;

/**
 * Crear contacto de emergencia (04.02/RF-012).
 */
final class CreateContactoEmergenciaAction
{
    public function __construct(
        private readonly PacienteAccessScope $scope,
        private readonly ParentescoResolver $parentescos,
        private readonly PacienteBranchResolver $branches,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        Paciente $paciente,
        array $attributes,
        User $actor,
        ?string $ip = null,
    ): ContactoEmergencia {
        $this->scope->assertCanAccess($actor, $paciente);

        $nombre = TutorIdentityRule::normalizeName((string) $attributes['nombre_completo']);
        $telefono = TutorPhoneRule::normalize((string) $attributes['telefono']);

        if (! TutorPhoneRule::isValidAlternate($telefono)) {
            throw new ContactoEmergenciaPhoneInvalidException;
        }

        $parentesco = $this->parentescos->resolve(
            isset($attributes['parentesco_id']) ? (int) $attributes['parentesco_id'] : null,
            null,
        );

        return DB::transaction(function () use ($paciente, $nombre, $telefono, $parentesco, $actor, $ip): ContactoEmergencia {
            $contacto = ContactoEmergencia::query()->create([
                'paciente_id' => $paciente->id,
                'nombre_completo' => $nombre,
                'parentesco_id' => $parentesco->id,
                'telefono' => $telefono,
                'version' => 1,
            ]);

            $contacto->refresh()->load('parentesco');

            event(new ContactoEmergenciaCreated(
                contactoId: (int) $contacto->id,
                pacienteId: (int) $paciente->id,
                snapshot: ContactoEmergenciaSnapshot::from($contacto),
                branchIds: $this->branches->for($paciente),
                actorId: $actor->id,
                ip: $ip,
            ));

            return $contacto;
        });
    }
}
