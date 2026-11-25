<?php

namespace App\Modules\Pacientes\Actions\Ficha;

use App\Models\User;
use App\Modules\Pacientes\Enums\EstadoFichaIngreso;
use App\Modules\Pacientes\Events\Ficha\FichaIngresoStatusChanged;
use App\Modules\Pacientes\Exceptions\FichaIngresoConcurrencyException;
use App\Modules\Pacientes\Exceptions\FichaIngresoNotFoundException;
use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Rules\FichaIngresoStatusRule;
use App\Modules\Pacientes\Support\FichaAuditAction;
use App\Modules\Pacientes\Support\FichaIngresoAccessScope;
use App\Modules\Pacientes\Support\FichaIngresoBranchResolver;
use App\Modules\Pacientes\Support\FichaIngresoRevisionWriter;
use App\Modules\Pacientes\Support\FichaIngresoSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Completar una ficha en borrador (04.03/RNF-007 bis).
 */
final class CompleteFichaIngresoAction
{
    public function __construct(
        private readonly FichaIngresoAccessScope $scope,
        private readonly FichaIngresoStatusRule $status,
        private readonly FichaIngresoRevisionWriter $revisions,
        private readonly FichaIngresoBranchResolver $branches,
    ) {}

    public function __invoke(
        Paciente $paciente,
        FichaIngreso $ficha,
        int $expectedVersion,
        User $actor,
        ?string $ip = null,
    ): FichaIngreso {
        $this->scope->assertCanAccess($actor, $ficha);

        return DB::transaction(function () use ($paciente, $ficha, $expectedVersion, $actor, $ip): FichaIngreso {
            $current = FichaIngreso::query()
                ->whereKey($ficha->id)
                ->where('paciente_id', $paciente->id)
                ->lockForUpdate()
                ->first();

            if ($current === null) {
                throw new FichaIngresoNotFoundException((int) $ficha->id);
            }

            if ((int) $current->version !== $expectedVersion) {
                throw new FichaIngresoConcurrencyException;
            }

            $this->status->assertCanComplete($current);
            $before = FichaIngresoSnapshot::from($current);

            $current->forceFill([
                'estado' => EstadoFichaIngreso::COMPLETA->value,
                'completada_en' => now(),
                'completada_por_id' => $actor->id,
                'version' => (int) $current->version + 1,
            ])->save();

            $revision = $this->revisions->record($current, FichaAuditAction::COMPLETE, $actor->id);
            $current->refresh()->load(['paciente', 'sucursalIngreso', 'tutorPrincipal']);

            event(new FichaIngresoStatusChanged(
                fichaId: (int) $current->id,
                pacienteId: (int) $current->paciente_id,
                before: $before,
                after: FichaIngresoSnapshot::from($current, (int) $revision->id),
                accion: FichaAuditAction::COMPLETE,
                critico: false,
                motivo: null,
                revisionId: (int) $revision->id,
                branchIds: $this->branches->for($current),
                actorId: $actor->id,
                ip: $ip,
            ));

            return $current;
        });
    }
}
