<?php

namespace App\Modules\Pacientes\Actions\Contactos;

use App\Models\User;
use App\Modules\Pacientes\Events\Contactos\ContactoEmergenciaUpdated;
use App\Modules\Pacientes\Exceptions\ContactoEmergenciaConcurrencyException;
use App\Modules\Pacientes\Exceptions\ContactoEmergenciaNotFoundException;
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
 * Editar contacto de emergencia (04.02/RF-012).
 */
final class UpdateContactoEmergenciaAction
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
        ContactoEmergencia $contacto,
        array $attributes,
        int $expectedVersion,
        User $actor,
        ?string $ip = null,
    ): ContactoEmergencia {
        $this->scope->assertCanAccess($actor, $paciente);

        return DB::transaction(function () use ($paciente, $contacto, $attributes, $expectedVersion, $actor, $ip): ContactoEmergencia {
            $current = ContactoEmergencia::query()
                ->whereKey($contacto->id)
                ->where('paciente_id', $paciente->id)
                ->lockForUpdate()
                ->first();

            if ($current === null) {
                throw new ContactoEmergenciaNotFoundException((int) $contacto->id);
            }

            if ((int) $current->version !== $expectedVersion) {
                throw new ContactoEmergenciaConcurrencyException;
            }

            $before = ContactoEmergenciaSnapshot::from($current);
            $changes = [];

            if (array_key_exists('nombre_completo', $attributes)) {
                $changes['nombre_completo'] = TutorIdentityRule::normalizeName((string) $attributes['nombre_completo']);
            }

            if (array_key_exists('telefono', $attributes)) {
                $telefono = TutorPhoneRule::normalize((string) $attributes['telefono']);

                if (! TutorPhoneRule::isValidAlternate($telefono)) {
                    throw new ContactoEmergenciaPhoneInvalidException;
                }

                $changes['telefono'] = $telefono;
            }

            if (array_key_exists('parentesco_id', $attributes)) {
                $parentesco = $this->parentescos->resolve((int) $attributes['parentesco_id'], null);
                $changes['parentesco_id'] = (int) $parentesco->id;
            }

            $realChanges = array_filter(
                $changes,
                static fn (mixed $value, string $field): bool => $current->{$field} !== $value,
                ARRAY_FILTER_USE_BOTH,
            );

            if ($realChanges === []) {
                return $current->refresh()->load('parentesco');
            }

            $current->fill($realChanges);
            $current->version = (int) $current->version + 1;
            $current->save();

            $current->refresh()->load('parentesco');

            event(new ContactoEmergenciaUpdated(
                contactoId: (int) $current->id,
                pacienteId: (int) $paciente->id,
                before: $before,
                after: ContactoEmergenciaSnapshot::from($current),
                changedFields: array_keys($realChanges),
                branchIds: $this->branches->for($paciente),
                actorId: $actor->id,
                ip: $ip,
            ));

            return $current;
        });
    }
}
