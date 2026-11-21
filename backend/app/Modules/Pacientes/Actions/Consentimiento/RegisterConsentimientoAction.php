<?php

namespace App\Modules\Pacientes\Actions\Consentimiento;

use App\Models\User;
use App\Modules\Pacientes\Enums\ResultadoConsentimiento;
use App\Modules\Pacientes\Events\Consentimiento\ConsentimientoRegistered;
use App\Modules\Pacientes\Exceptions\ConsentimientoAlreadyActiveException;
use App\Modules\Pacientes\Exceptions\ConsentimientoTipoInactiveException;
use App\Modules\Pacientes\Exceptions\ConsentimientoTipoNotFoundException;
use App\Modules\Pacientes\Exceptions\FichaIngresoNotFoundException;
use App\Modules\Pacientes\Models\Consentimiento;
use App\Modules\Pacientes\Models\ConsentimientoTipo;
use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Rules\ConsentimientoDateRule;
use App\Modules\Pacientes\Rules\ConsentimientoRules;
use App\Modules\Pacientes\Rules\TutorIdentityRule;
use App\Modules\Pacientes\Support\ConsentimientoSnapshot;
use App\Modules\Pacientes\Support\FichaIngresoAccessScope;
use App\Modules\Pacientes\Support\FichaIngresoBranchResolver;
use App\Modules\Pacientes\Support\PacienteAgeCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Registra un consentimiento otorgado o negado (04.03 Fase B, RF-003).
 */
final class RegisterConsentimientoAction
{
    public function __construct(
        private readonly FichaIngresoAccessScope $scope,
        private readonly ConsentimientoRules $rules,
        private readonly ConsentimientoDateRule $dateRule,
        private readonly FichaIngresoBranchResolver $branches,
        private readonly PacienteAgeCalculator $age,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        FichaIngreso $ficha,
        array $attributes,
        User $actor,
        ?string $ip = null,
    ): Consentimiento {
        $this->scope->assertCanAccess($actor, $ficha);

        return DB::transaction(function () use ($ficha, $attributes, $actor, $ip): Consentimiento {
            $current = FichaIngreso::query()
                ->whereKey($ficha->id)
                ->lockForUpdate()
                ->first();

            if ($current === null) {
                throw new FichaIngresoNotFoundException((int) $ficha->id);
            }

            $this->rules->assertFichaEditable($current);
            $current->loadMissing('paciente');
            $paciente = $current->paciente;

            if ($paciente === null) {
                throw new FichaIngresoNotFoundException((int) $ficha->id);
            }

            $this->rules->assertTieneTutores($paciente);
            $tutorId = (int) $attributes['tutor_id'];
            $this->rules->assertTutorAsociado($paciente, $tutorId);

            $tipoId = (int) $attributes['consentimiento_tipo_id'];
            $tipo = ConsentimientoTipo::query()->find($tipoId);

            if ($tipo === null) {
                throw new ConsentimientoTipoNotFoundException($tipoId);
            }

            if (! $tipo->activo) {
                throw new ConsentimientoTipoInactiveException;
            }

            $today = $this->age->today();
            $fecha = CarbonImmutable::parse((string) $attributes['fecha'], $today->getTimezone())->startOfDay();
            $this->dateRule->assertNotFuture($fecha, $today);

            $resultado = ResultadoConsentimiento::from((string) $attributes['resultado']);

            if ($resultado->esOtorgado() && Consentimiento::query()
                ->where('ficha_ingreso_id', $current->id)
                ->where('consentimiento_tipo_id', $tipo->id)
                ->where('tutor_id', $tutorId)
                ->vigentes()
                ->exists()) {
                throw new ConsentimientoAlreadyActiveException;
            }

            $consentimiento = Consentimiento::query()->create([
                'ficha_ingreso_id' => $current->id,
                'consentimiento_tipo_id' => $tipo->id,
                'tutor_id' => $tutorId,
                'resultado' => $resultado->value,
                'fecha' => $fecha->toDateString(),
                'motivo' => TutorIdentityRule::normalizeOptional($attributes['motivo'] ?? null),
                'usuario_id' => $actor->id,
                'revoca_a_id' => null,
                'created_at' => now(),
            ]);

            $consentimiento->load(['tipo', 'tutor']);

            event(new ConsentimientoRegistered(
                consentimientoId: (int) $consentimiento->id,
                fichaId: (int) $current->id,
                pacienteId: (int) $current->paciente_id,
                resultado: $resultado->value,
                snapshot: ConsentimientoSnapshot::from($consentimiento),
                branchIds: $this->branches->for($current),
                actorId: $actor->id,
                ip: $ip,
            ));

            return $consentimiento;
        });
    }
}
