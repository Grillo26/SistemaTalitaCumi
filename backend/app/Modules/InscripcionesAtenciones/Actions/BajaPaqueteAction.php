<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\EstadoPaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Enums\MotivoBajaPaquete;
use App\Modules\InscripcionesAtenciones\Events\PaqueteRetirado;
use App\Modules\InscripcionesAtenciones\Exceptions\FechaBajaPaqueteInvalidaException;
use App\Modules\InscripcionesAtenciones\Exceptions\PaqueteNotFoundException;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Rules\MotivoBajaPaqueteRule;
use App\Modules\InscripcionesAtenciones\Rules\PaqueteStateTransitionRule;
use App\Modules\InscripcionesAtenciones\Support\PaquetePeriod;
use App\Modules\InscripcionesAtenciones\Support\PaqueteSnapshot;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use Illuminate\Support\Facades\DB;

/**
 * [Paquetes Sesiones Individuales] RF-014 / CU-03 / RN-010.
 *
 * No consulta Agenda ni genera/cancela turnos.
 */
final class BajaPaqueteAction
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(int $id, array $data, User $actor, ?string $ip = null): PaqueteSesiones
    {
        $motivo = MotivoBajaPaquete::from((string) $data['motivo']);
        $detalle = isset($data['motivo_detalle']) && is_string($data['motivo_detalle'])
            ? trim($data['motivo_detalle'])
            : null;
        $fechaBaja = (string) $data['fecha_baja'];

        if ($fechaBaja > PaquetePeriod::today()) {
            throw new FechaBajaPaqueteInvalidaException(
                'La fecha de baja no puede ser futura.',
            );
        }

        MotivoBajaPaqueteRule::assertValido($motivo, $detalle);

        return DB::transaction(function () use ($id, $motivo, $detalle, $fechaBaja, $actor, $ip): PaqueteSesiones {
            $paquete = PaqueteSesiones::query()->lockForUpdate()->find($id);

            if ($paquete === null) {
                throw new PaqueteNotFoundException($id);
            }

            if (! $this->access->puedeAcceder($actor->id, (int) $paquete->sucursal_id)) {
                throw new BranchAccessDeniedException;
            }

            PaqueteStateTransitionRule::assertPuedeRetirar($paquete->estado);

            $before = PaqueteSnapshot::from($paquete);

            $paquete->estado = EstadoPaqueteSesiones::RETIRADO;
            $paquete->save();
            $paquete->refresh();

            $after = PaqueteSnapshot::from($paquete);

            event(new PaqueteRetirado(
                paqueteId: (int) $paquete->id,
                sucursalId: (int) $paquete->sucursal_id,
                pacienteId: (int) $paquete->paciente_id,
                servicioId: (int) $paquete->servicio_id,
                motivo: $motivo,
                motivoDetalle: $detalle,
                fechaBaja: $fechaBaja,
                actorId: $actor->id,
                ip: $ip,
                before: $before,
                after: $after,
            ));

            return $paquete;
        });
    }
}
