<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcion;
use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcionSala;
use App\Modules\InscripcionesAtenciones\Enums\MotivoBaja;
use App\Modules\InscripcionesAtenciones\Events\InscripcionRetirada;
use App\Modules\InscripcionesAtenciones\Exceptions\FechaBajaInvalidaException;
use App\Modules\InscripcionesAtenciones\Exceptions\InscripcionNotFoundException;
use App\Modules\InscripcionesAtenciones\Exceptions\InscripcionVencidaNoRetirableException;
use App\Modules\InscripcionesAtenciones\Exceptions\TransicionEstadoNoPermitidaException;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Rules\InscripcionStateTransitionRule;
use App\Modules\InscripcionesAtenciones\Rules\MotivoBajaRule;
use App\Modules\InscripcionesAtenciones\Support\EstadoInscripcionResolver;
use App\Modules\InscripcionesAtenciones\Support\InscripcionPeriod;
use App\Modules\InscripcionesAtenciones\Support\InscripcionSnapshot;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use Illuminate\Support\Facades\DB;

/**
 * RF-011 / CU-03 / RN-009: baja con motivo y fecha, sin borrado físico.
 *
 * No se consulta Agenda: el bloqueo de asistencias es responsabilidad del
 * módulo 07 a partir del evento InscripcionRetirada.
 */
final class BajaInscripcionAction
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(int $id, array $data, User $actor, ?string $ip = null): InscripcionSala
    {
        $motivo = MotivoBaja::from((string) $data['motivo']);
        $detalle = isset($data['motivo_detalle']) && is_string($data['motivo_detalle'])
            ? trim($data['motivo_detalle'])
            : null;
        $fechaBaja = (string) $data['fecha_baja'];

        if ($fechaBaja > InscripcionPeriod::today()) {
            throw new FechaBajaInvalidaException('La fecha de baja no puede ser futura.');
        }

        MotivoBajaRule::assertValido($motivo, $detalle);

        return DB::transaction(function () use ($id, $motivo, $detalle, $fechaBaja, $actor, $ip): InscripcionSala {
            $inscripcion = InscripcionSala::query()->lockForUpdate()->find($id);

            if ($inscripcion === null) {
                throw new InscripcionNotFoundException($id);
            }

            if (! $this->access->puedeAcceder($actor->id, (int) $inscripcion->sucursal_id)) {
                throw new BranchAccessDeniedException;
            }

            $efectivo = EstadoInscripcionResolver::efectivoSala($inscripcion);

            if ($efectivo === EstadoInscripcion::VENCIDA) {
                throw new InscripcionVencidaNoRetirableException;
            }

            if ($efectivo === EstadoInscripcion::RENOVADA) {
                throw new TransicionEstadoNoPermitidaException(
                    EstadoInscripcion::RENOVADA,
                    EstadoInscripcion::RETIRADA,
                );
            }

            InscripcionStateTransitionRule::assertPuedeRetirar($inscripcion->estado);

            $before = InscripcionSnapshot::from($inscripcion);

            $inscripcion->estado = EstadoInscripcionSala::RETIRADA;
            $inscripcion->save();
            $inscripcion->refresh();

            $after = InscripcionSnapshot::from($inscripcion);

            event(new InscripcionRetirada(
                inscripcionId: (int) $inscripcion->id,
                sucursalId: (int) $inscripcion->sucursal_id,
                pacienteId: (int) $inscripcion->paciente_id,
                salaId: (int) $inscripcion->sala_id,
                motivo: $motivo,
                motivoDetalle: $detalle,
                fechaBaja: $fechaBaja,
                actorId: $actor->id,
                ip: $ip,
                before: $before,
                after: $after,
            ));

            return $inscripcion;
        });
    }
}
