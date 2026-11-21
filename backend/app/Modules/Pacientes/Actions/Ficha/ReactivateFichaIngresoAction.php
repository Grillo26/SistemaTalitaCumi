<?php

namespace App\Modules\Pacientes\Actions\Ficha;

use App\Models\User;
use App\Modules\Pacientes\Events\Ficha\FichaIngresoStatusChanged;
use App\Modules\Pacientes\Exceptions\FichaIngresoAlreadyExistsException;
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
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Reactivar una ficha archivada (04.03/RN-005).
 */
final class ReactivateFichaIngresoAction
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
        string $motivo,
        int $expectedVersion,
        User $actor,
        ?string $ip = null,
    ): FichaIngreso {
        $this->scope->assertCanAccess($actor, $ficha);

        return DB::transaction(function () use ($paciente, $ficha, $motivo, $expectedVersion, $actor, $ip): FichaIngreso {
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

            $this->status->assertCanReactivate($current);

            $otraAbierta = FichaIngreso::query()
                ->vigente()
                ->where('paciente_id', $paciente->id)
                ->whereKeyNot($current->id)
                ->exists();

            if ($otraAbierta) {
                throw new FichaIngresoAlreadyExistsException;
            }

            $before = FichaIngresoSnapshot::from($current);
            $motivo = trim($motivo);
            $target = $this->status->reactivationTarget($current);

            $current->forceFill([
                'estado' => $target->value,
                'estado_anterior' => null,
                'archivada_en' => null,
                'archivada_por_id' => null,
                'motivo_archivado' => null,
                'version' => (int) $current->version + 1,
            ]);

            try {
                $current->save();
            } catch (QueryException $exception) {
                if (str_contains($exception->getMessage(), 'ficha_ingreso_abierta_uk')) {
                    throw new FichaIngresoAlreadyExistsException;
                }

                throw $exception;
            }

            $revision = $this->revisions->record($current, FichaAuditAction::REACTIVATE, $actor->id);
            $current->refresh()->load(['paciente', 'sucursalIngreso', 'tutorPrincipal']);

            event(new FichaIngresoStatusChanged(
                fichaId: (int) $current->id,
                pacienteId: (int) $current->paciente_id,
                before: $before,
                after: FichaIngresoSnapshot::from($current, (int) $revision->id),
                accion: FichaAuditAction::REACTIVATE,
                critico: false,
                motivo: $motivo,
                revisionId: (int) $revision->id,
                branchIds: $this->branches->for($current),
                actorId: $actor->id,
                ip: $ip,
            ));

            return $current;
        });
    }
}
