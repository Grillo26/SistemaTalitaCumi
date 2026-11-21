<?php

namespace App\Modules\Pacientes\Actions\Consentimiento;

use App\Models\User;
use App\Modules\Pacientes\Enums\ResultadoConsentimiento;
use App\Modules\Pacientes\Events\Consentimiento\ConsentimientoRevoked;
use App\Modules\Pacientes\Exceptions\ConsentimientoNotFoundException;
use App\Modules\Pacientes\Exceptions\ConsentimientoNotRevocableException;
use App\Modules\Pacientes\Exceptions\FichaIngresoNotFoundException;
use App\Modules\Pacientes\Models\Consentimiento;
use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Rules\ConsentimientoRules;
use App\Modules\Pacientes\Support\ConsentimientoSnapshot;
use App\Modules\Pacientes\Support\FichaIngresoAccessScope;
use App\Modules\Pacientes\Support\FichaIngresoBranchResolver;
use App\Modules\Pacientes\Support\PacienteAgeCalculator;
use Illuminate\Support\Facades\DB;

/**
 * Revoca un consentimiento mediante un evento nuevo e inmutable (04.03 Fase B).
 *
 * El consentimiento original no se modifica ni se borra. El motivo queda en
 * el evento de revocación y no se copia a la bitácora.
 */
final class RevokeConsentimientoAction
{
    public function __construct(
        private readonly FichaIngresoAccessScope $scope,
        private readonly ConsentimientoRules $rules,
        private readonly FichaIngresoBranchResolver $branches,
        private readonly PacienteAgeCalculator $age,
    ) {}

    public function __invoke(
        FichaIngreso $ficha,
        Consentimiento $consentimiento,
        string $motivo,
        User $actor,
        ?string $ip = null,
    ): Consentimiento {
        $this->scope->assertCanAccess($actor, $ficha);

        return DB::transaction(function () use ($ficha, $consentimiento, $motivo, $actor, $ip): Consentimiento {
            $current = FichaIngreso::query()
                ->whereKey($ficha->id)
                ->lockForUpdate()
                ->first();

            if ($current === null) {
                throw new FichaIngresoNotFoundException((int) $ficha->id);
            }

            $this->rules->assertFichaEditable($current);

            $original = Consentimiento::query()
                ->whereKey($consentimiento->id)
                ->where('ficha_ingreso_id', $current->id)
                ->lockForUpdate()
                ->first();

            if ($original === null) {
                throw new ConsentimientoNotFoundException((int) $consentimiento->id);
            }

            if (! $original->resultado->esOtorgado() || ! $original->estaVigente()) {
                throw new ConsentimientoNotRevocableException;
            }

            $revocacion = Consentimiento::query()->create([
                'ficha_ingreso_id' => $original->ficha_ingreso_id,
                'consentimiento_tipo_id' => $original->consentimiento_tipo_id,
                'tutor_id' => $original->tutor_id,
                'resultado' => ResultadoConsentimiento::REVOCADO->value,
                'fecha' => $this->age->today()->toDateString(),
                'motivo' => trim($motivo),
                'usuario_id' => $actor->id,
                'revoca_a_id' => $original->id,
                'created_at' => now(),
            ]);

            $revocacion->load(['tipo', 'tutor']);

            event(new ConsentimientoRevoked(
                consentimientoId: (int) $revocacion->id,
                originalId: (int) $original->id,
                fichaId: (int) $current->id,
                pacienteId: (int) $current->paciente_id,
                snapshot: ConsentimientoSnapshot::from($revocacion),
                branchIds: $this->branches->for($current),
                actorId: $actor->id,
                ip: $ip,
            ));

            return $revocacion;
        });
    }
}
