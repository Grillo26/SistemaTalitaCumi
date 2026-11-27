<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcion;
use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcionSala;
use App\Modules\InscripcionesAtenciones\Enums\EstadoPaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Events\InscripcionReactivada;
use App\Modules\InscripcionesAtenciones\Exceptions\AccionEstadoNoPermitidaException;
use App\Modules\InscripcionesAtenciones\Exceptions\EntidadEstadoNotFoundException;
use App\Modules\InscripcionesAtenciones\Exceptions\EstadoDesactualizadoException;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Rules\EstadoInscripcionTransitionRule;
use App\Modules\InscripcionesAtenciones\Support\EstadoInscripcionResolver;
use App\Modules\InscripcionesAtenciones\Support\EstadoPermission;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use Illuminate\Support\Facades\DB;

/**
 * [Estados Paciente e Inscripción] RF-004 / RN-003 / RN-010 / CA-002.
 */
final class ReactivarInscripcionAction
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{estado_efectivo: EstadoInscripcion}
     */
    public function __invoke(
        TipoEntidadVigencia $tipo,
        int $id,
        array $data,
        User $actor,
        ?string $ip = null,
    ): array {
        if (! $actor->can(EstadoPermission::INSCRIPTION_REACTIVATE)
            || ! $actor->can(EstadoPermission::viewFor($tipo))) {
            throw new AccionEstadoNoPermitidaException;
        }

        $esperado = (string) $data['estado_esperado'];

        return DB::transaction(function () use ($tipo, $id, $actor, $ip, $esperado): array {
            [$modelo, $efectivo] = $this->load($tipo, $id);

            if (! $this->access->puedeAcceder($actor->id, (int) $modelo->sucursal_id)) {
                throw new BranchAccessDeniedException;
            }

            if ($esperado !== $efectivo->value) {
                throw new EstadoDesactualizadoException($efectivo->value, $esperado);
            }

            EstadoInscripcionTransitionRule::assertPuedeReactivar($efectivo);

            $before = $this->snapshot($modelo);
            $modelo->estado = $tipo === TipoEntidadVigencia::INSCRIPCION_SALA
                ? EstadoInscripcionSala::ACTIVA
                : EstadoPaqueteSesiones::ACTIVO;
            $modelo->save();
            $modelo->refresh();
            $after = $this->snapshot($modelo);
            $estadoEfectivo = $tipo === TipoEntidadVigencia::INSCRIPCION_SALA
                ? EstadoInscripcionResolver::efectivoSala($modelo)
                : EstadoInscripcionResolver::efectivoPaquete($modelo);

            event(new InscripcionReactivada(
                tipo: $tipo,
                entidadId: (int) $modelo->id,
                sucursalId: (int) $modelo->sucursal_id,
                pacienteId: (int) $modelo->paciente_id,
                from: $efectivo,
                to: EstadoInscripcion::ACTIVA,
                estadoEfectivo: $estadoEfectivo,
                actorId: $actor->id,
                ip: $ip,
                before: $before,
                after: $after,
            ));

            return ['estado_efectivo' => $estadoEfectivo];
        });
    }

    /**
     * @return array{0: InscripcionSala|PaqueteSesiones, 1: EstadoInscripcion}
     */
    private function load(TipoEntidadVigencia $tipo, int $id): array
    {
        if ($tipo === TipoEntidadVigencia::INSCRIPCION_SALA) {
            $modelo = InscripcionSala::query()->lockForUpdate()->find($id);

            if ($modelo === null) {
                throw new EntidadEstadoNotFoundException($tipo, $id);
            }

            return [$modelo, EstadoInscripcionResolver::efectivoSala($modelo)];
        }

        $modelo = PaqueteSesiones::query()->lockForUpdate()->find($id);

        if ($modelo === null) {
            throw new EntidadEstadoNotFoundException($tipo, $id);
        }

        return [$modelo, EstadoInscripcionResolver::efectivoPaquete($modelo)];
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(InscripcionSala|PaqueteSesiones $modelo): array
    {
        return [
            'id' => (int) $modelo->id,
            'paciente_id' => (int) $modelo->paciente_id,
            'sucursal_id' => (int) $modelo->sucursal_id,
            'fecha_inicio' => $modelo->fecha_inicio->toDateString(),
            'fecha_fin' => $modelo->fecha_fin->toDateString(),
            'estado' => $modelo->estado->value,
        ];
    }
}
