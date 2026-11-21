<?php

namespace App\Modules\Pacientes\Actions\Observacion;

use App\Models\User;
use App\Modules\Pacientes\Events\Observacion\ObservacionCreada;
use App\Modules\Pacientes\Exceptions\FichaIngresoArchivedException;
use App\Modules\Pacientes\Exceptions\FichaIngresoNotFoundException;
use App\Modules\Pacientes\Exceptions\ObservacionReferenciaInvalidaException;
use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Models\FichaIngresoObservacion;
use App\Modules\Pacientes\Rules\ObservacionDateRule;
use App\Modules\Pacientes\Support\FichaIngresoAccessScope;
use App\Modules\Pacientes\Support\FichaIngresoBranchResolver;
use App\Modules\Pacientes\Support\ObservacionSnapshot;
use App\Modules\Pacientes\Support\PacienteAgeCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Agrega una observación fechada e inmutable (04.03 Fase C, RF-006).
 */
final class CreateObservacionAction
{
    public function __construct(
        private readonly FichaIngresoAccessScope $scope,
        private readonly ObservacionDateRule $dateRule,
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
    ): FichaIngresoObservacion {
        $this->scope->assertCanAccess($actor, $ficha);

        return DB::transaction(function () use ($ficha, $attributes, $actor, $ip): FichaIngresoObservacion {
            $current = FichaIngreso::query()
                ->whereKey($ficha->id)
                ->lockForUpdate()
                ->first();

            if ($current === null) {
                throw new FichaIngresoNotFoundException((int) $ficha->id);
            }

            if ($current->estaArchivada()) {
                throw new FichaIngresoArchivedException;
            }

            $today = $this->age->today();
            $fecha = CarbonImmutable::parse((string) $attributes['fecha'], $today->getTimezone())->startOfDay();
            $this->dateRule->assertNotFuture($fecha, $today);

            $referenciaId = isset($attributes['observacion_referencia_id'])
                ? (int) $attributes['observacion_referencia_id']
                : null;

            if ($referenciaId !== null) {
                $referencia = FichaIngresoObservacion::query()
                    ->whereKey($referenciaId)
                    ->where('ficha_ingreso_id', $current->id)
                    ->first();

                if ($referencia === null) {
                    throw new ObservacionReferenciaInvalidaException;
                }
            }

            $observacion = FichaIngresoObservacion::query()->create([
                'ficha_ingreso_id' => $current->id,
                'fecha' => $fecha->toDateString(),
                'texto' => trim((string) $attributes['texto']),
                'observacion_referencia_id' => $referenciaId,
                'usuario_id' => $actor->id,
                'created_at' => now(),
            ]);

            $observacion->load(['usuario', 'referencia']);

            event(new ObservacionCreada(
                observacionId: (int) $observacion->id,
                fichaId: (int) $current->id,
                pacienteId: (int) $current->paciente_id,
                snapshot: ObservacionSnapshot::from($observacion),
                branchIds: $this->branches->for($current),
                actorId: $actor->id,
                ip: $ip,
            ));

            return $observacion;
        });
    }
}
