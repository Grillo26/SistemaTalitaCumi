<?php

namespace App\Modules\Profesionales\Actions\Especialidades\Concerns;

use App\Modules\Profesionales\Events\Especialidades\EspecialidadStatusChanged;
use App\Modules\Profesionales\Exceptions\EspecialidadConcurrencyException;
use App\Modules\Profesionales\Exceptions\EspecialidadNotFoundException;
use App\Modules\Profesionales\Models\Especialidad;
use App\Modules\Profesionales\Rules\EspecialidadStatusChangeRule;
use App\Modules\Profesionales\Support\EspecialidadSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Cambio de estado de especialidad con motivo y concurrencia (03/RN-20).
 */
trait ChangesEspecialidadStatus
{
    protected function changeStatus(
        int $especialidadId,
        bool $targetStatus,
        string $reason,
        int $expectedVersion,
        ?int $actorId,
        ?string $ip,
        ?callable $beforeChange = null,
    ): Especialidad {
        $rule = new EspecialidadStatusChangeRule;
        $reason = $rule->reason($reason);

        return DB::transaction(function () use ($especialidadId, $targetStatus, $reason, $expectedVersion, $actorId, $ip, $rule, $beforeChange): Especialidad {
            $especialidad = Especialidad::query()
                ->whereKey($especialidadId)
                ->lockForUpdate()
                ->first();

            if ($especialidad === null) {
                throw new EspecialidadNotFoundException($especialidadId);
            }

            if ($especialidad->version !== $expectedVersion) {
                throw new EspecialidadConcurrencyException;
            }

            $rule->assertDifferent((bool) $especialidad->activo, $targetStatus);

            if ($beforeChange !== null) {
                $beforeChange($especialidad);
            }

            $before = EspecialidadSnapshot::from($especialidad);

            $especialidad->forceFill([
                'activo' => $targetStatus,
                'motivo_estado' => $targetStatus ? null : $reason,
                'estado_cambiado_en' => now(),
                'version' => $especialidad->version + 1,
            ])->save();

            $especialidad->refresh();

            event(new EspecialidadStatusChanged(
                especialidadId: $especialidad->id,
                before: $before,
                after: EspecialidadSnapshot::from($especialidad),
                activo: $targetStatus,
                reason: $reason,
                actorId: $actorId,
                ip: $ip,
            ));

            return $especialidad;
        });
    }
}
