<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\EstadoAtencion;
use App\Modules\InscripcionesAtenciones\Enums\MotivoBajaAtencion;
use App\Modules\InscripcionesAtenciones\Enums\TipoAtencion;
use App\Modules\InscripcionesAtenciones\Events\AtencionRetirada;
use App\Modules\InscripcionesAtenciones\Exceptions\AtencionDiagnosticoNotFoundException;
use App\Modules\InscripcionesAtenciones\Exceptions\AtencionEntrevistaNotFoundException;
use App\Modules\InscripcionesAtenciones\Exceptions\FechaBajaInvalidaException;
use App\Modules\InscripcionesAtenciones\Models\AtencionDiagnostico;
use App\Modules\InscripcionesAtenciones\Models\AtencionEntrevista;
use App\Modules\InscripcionesAtenciones\Rules\AtencionStateTransitionRule;
use App\Modules\InscripcionesAtenciones\Rules\MotivoBajaAtencionRule;
use App\Modules\InscripcionesAtenciones\Support\AtencionPeriod;
use App\Modules\InscripcionesAtenciones\Support\AtencionSnapshot;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use Illuminate\Support\Facades\DB;

/**
 * [Entrevista y Diagnóstico] RF-011 / RN-009 / CU-03. CA-007.
 *
 * No hay borrado físico. La atención pasa a `retirada` y el motivo/fecha
 * quedan en bitácora, igual que en Salas y Paquetes.
 */
final class BajaAtencionAction
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(
        TipoAtencion $tipo,
        int $id,
        array $data,
        User $actor,
        ?string $ip = null,
    ): AtencionEntrevista|AtencionDiagnostico {
        $motivo = MotivoBajaAtencion::from((string) $data['motivo']);
        $detalle = isset($data['motivo_detalle']) && is_string($data['motivo_detalle'])
            ? trim($data['motivo_detalle'])
            : null;
        $fechaBaja = (string) $data['fecha_baja'];

        if ($fechaBaja > AtencionPeriod::today()) {
            throw new FechaBajaInvalidaException('La fecha de baja no puede ser futura.');
        }

        MotivoBajaAtencionRule::assertValido($motivo, $detalle);

        return DB::transaction(function () use ($tipo, $id, $motivo, $detalle, $fechaBaja, $actor, $ip) {
            if ($tipo === TipoAtencion::ENTREVISTA) {
                $atencion = AtencionEntrevista::query()->lockForUpdate()->find($id);

                if ($atencion === null) {
                    throw new AtencionEntrevistaNotFoundException($id);
                }

                if (! $this->access->puedeAcceder($actor->id, (int) $atencion->sucursal_id)) {
                    throw new BranchAccessDeniedException;
                }

                AtencionStateTransitionRule::assertPuedeRetirar($atencion->estado);
                $before = AtencionSnapshot::entrevista($atencion);
                $atencion->estado = EstadoAtencion::RETIRADA;
                $atencion->save();
                $atencion->refresh();
                $after = AtencionSnapshot::entrevista($atencion);

                event(new AtencionRetirada(
                    tipo: $tipo,
                    atencionId: (int) $atencion->id,
                    sucursalId: (int) $atencion->sucursal_id,
                    pacienteId: (int) $atencion->paciente_id,
                    motivo: $motivo,
                    motivoDetalle: $detalle,
                    fechaBaja: $fechaBaja,
                    actorId: $actor->id,
                    ip: $ip,
                    before: $before,
                    after: $after,
                ));

                return $atencion;
            }

            $atencion = AtencionDiagnostico::query()->lockForUpdate()->find($id);

            if ($atencion === null) {
                throw new AtencionDiagnosticoNotFoundException($id);
            }

            if (! $this->access->puedeAcceder($actor->id, (int) $atencion->sucursal_id)) {
                throw new BranchAccessDeniedException;
            }

            AtencionStateTransitionRule::assertPuedeRetirar($atencion->estado);
            $before = AtencionSnapshot::diagnostico($atencion);
            $atencion->estado = EstadoAtencion::RETIRADA;
            $atencion->save();
            $atencion->refresh()->load(['profesionales:id,nombre_completo', 'requisitos']);
            $after = AtencionSnapshot::diagnostico($atencion);

            event(new AtencionRetirada(
                tipo: $tipo,
                atencionId: (int) $atencion->id,
                sucursalId: (int) $atencion->sucursal_id,
                pacienteId: (int) $atencion->paciente_id,
                motivo: $motivo,
                motivoDetalle: $detalle,
                fechaBaja: $fechaBaja,
                actorId: $actor->id,
                ip: $ip,
                before: $before,
                after: $after,
            ));

            return $atencion;
        });
    }
}
