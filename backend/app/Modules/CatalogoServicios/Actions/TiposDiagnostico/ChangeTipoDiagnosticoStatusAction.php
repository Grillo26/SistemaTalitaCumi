<?php

namespace App\Modules\CatalogoServicios\Actions\TiposDiagnostico;

use App\Modules\CatalogoServicios\Events\TipoDiagnosticoStatusChanged;
use App\Modules\CatalogoServicios\Exceptions\CatalogoConcurrencyException;
use App\Modules\CatalogoServicios\Exceptions\TipoDiagnosticoNotFoundException;
use App\Modules\CatalogoServicios\Models\TipoDiagnostico;
use App\Modules\CatalogoServicios\Rules\MotivoObligatorioRule;
use App\Modules\CatalogoServicios\Rules\TipoDiagnosticoStateRule;
use App\Modules\CatalogoServicios\Support\TipoDiagnosticoSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Activar o desactivar tipo de diagnóstico (RF-D05 / RN-D09).
 */
final class ChangeTipoDiagnosticoStatusAction
{
    public function __invoke(
        int $tipoDiagnosticoId,
        bool $targetStatus,
        string $motivo,
        int $version,
        ?int $actorId,
        ?string $ip,
    ): TipoDiagnostico {
        return DB::transaction(function () use (
            $tipoDiagnosticoId,
            $targetStatus,
            $motivo,
            $version,
            $actorId,
            $ip,
        ): TipoDiagnostico {
            $tipo = TipoDiagnostico::query()->lockForUpdate()->find($tipoDiagnosticoId);

            if ($tipo === null) {
                throw new TipoDiagnosticoNotFoundException($tipoDiagnosticoId);
            }

            if ((int) $tipo->version !== $version) {
                throw new CatalogoConcurrencyException;
            }

            TipoDiagnosticoStateRule::assertStatusDifferent((bool) $tipo->activo, $targetStatus);

            $reason = MotivoObligatorioRule::require($motivo);
            $before = TipoDiagnosticoSnapshot::from($tipo);

            $tipo->activo = $targetStatus;
            $tipo->version = (int) $tipo->version + 1;
            $tipo->save();
            $tipo->load(['servicio.area', 'especialidades']);

            event(new TipoDiagnosticoStatusChanged(
                tipoDiagnosticoId: $tipo->id,
                before: $before,
                after: TipoDiagnosticoSnapshot::from($tipo),
                activo: $targetStatus,
                motivo: $reason,
                actorId: $actorId,
                ip: $ip,
            ));

            return $tipo->refresh()->load(['servicio.area', 'servicio.tipoServicio', 'especialidades']);
        });
    }
}
