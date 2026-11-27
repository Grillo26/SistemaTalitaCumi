<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\PagoInscripcionContract;
use App\Modules\InscripcionesAtenciones\Contracts\PagoPaqueteContract;
use App\Modules\InscripcionesAtenciones\Contracts\TurnosFuturosInscripcionContract;
use App\Modules\InscripcionesAtenciones\Contracts\TurnosFuturosPaqueteContract;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Exceptions\AccionEstadoNoPermitidaException;
use App\Modules\InscripcionesAtenciones\Exceptions\EntidadEstadoNotFoundException;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Support\EstadoPermission;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Pacientes\Support\PacientesPermission;
use Throwable;

/**
 * [Estados Paciente e Inscripción] RF-016 / RN-010 / CA-002 / CU-01 FE-01.
 *
 * Pre-chequeos separados de las Actions. Nunca bloquean: si Agenda o Pagos
 * no están disponibles, devuelven disponible=false y la UI muestra
 * "verificación no disponible", nunca "sin turnos" ni "sin saldo".
 */
final class PreChequeoEstadoQuery
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly TurnosFuturosInscripcionContract $turnosInscripcion,
        private readonly TurnosFuturosPaqueteContract $turnosPaquete,
        private readonly PagoInscripcionContract $pagoInscripcion,
        private readonly PagoPaqueteContract $pagoPaquete,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function inscripcion(
        TipoEntidadVigencia $tipo,
        int $id,
        string $accion,
        User $actor,
    ): array {
        $permission = match ($accion) {
            'pausar' => EstadoPermission::INSCRIPTION_PAUSE,
            'reactivar' => EstadoPermission::INSCRIPTION_REACTIVATE,
            'retirar' => EstadoPermission::withdrawFor($tipo),
            default => null,
        };

        if ($permission === null
            || ! $actor->can($permission)
            || ! $actor->can(EstadoPermission::viewFor($tipo))) {
            throw new AccionEstadoNoPermitidaException;
        }

        $modelo = $this->find($tipo, $id);

        if (! $this->access->puedeAcceder($actor->id, (int) $modelo->sucursal_id)) {
            throw new BranchAccessDeniedException;
        }

        return [
            'turnos' => $accion === 'reactivar'
                ? null
                : $this->turnos($tipo, $id),
            'pago' => $accion === 'pausar'
                ? null
                : $this->pago($tipo, $id),
        ];
    }

    /**
     * @param  list<array{tipo: TipoEntidadVigencia, id: int, sucursal_id: int}>  $inscripciones
     * @return array<string, mixed>
     */
    public function paciente(array $inscripciones, User $actor): array
    {
        if (! $actor->can(PacientesPermission::PATIENT_RETIRE)) {
            throw new AccionEstadoNoPermitidaException;
        }

        $items = [];
        $turnosDisponibles = true;
        $pagoDisponible = true;
        $turnos = [];
        $pagos = [];

        foreach ($inscripciones as $item) {
            if (! $this->access->puedeAcceder($actor->id, $item['sucursal_id'])) {
                throw new BranchAccessDeniedException;
            }

            $turno = $this->turnos($item['tipo'], $item['id']);
            $pago = $this->pago($item['tipo'], $item['id']);
            $turnosDisponibles = $turnosDisponibles && $turno['disponible'];
            $pagoDisponible = $pagoDisponible && $pago['disponible'];

            $turnos[] = ['tipo_entidad' => $item['tipo']->value, 'id' => $item['id'], ...$turno];
            $pagos[] = ['tipo_entidad' => $item['tipo']->value, 'id' => $item['id'], ...$pago];
            $items[] = $item;
        }

        return [
            'items' => $items,
            'turnos' => [
                'disponible' => $turnosDisponibles,
                'mensaje' => $turnosDisponibles
                    ? null
                    : 'Verificación de turnos no disponible.',
                'detalle' => $turnos,
            ],
            'pago' => [
                'disponible' => $pagoDisponible,
                'mensaje' => $pagoDisponible
                    ? null
                    : 'Estado de pago no disponible.',
                'detalle' => $pagos,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function turnos(TipoEntidadVigencia $tipo, int $id): array
    {
        $contract = $tipo === TipoEntidadVigencia::INSCRIPCION_SALA
            ? $this->turnosInscripcion
            : $this->turnosPaquete;

        if (! $contract->disponible()) {
            return [
                'disponible' => false,
                'mensaje' => 'Verificación de turnos no disponible.',
                'data' => null,
            ];
        }

        try {
            $resumen = $tipo === TipoEntidadVigencia::INSCRIPCION_SALA
                ? $this->turnosInscripcion->resumen($id)
                : $this->turnosPaquete->resumen($id);

            return [
                'disponible' => true,
                'mensaje' => null,
                'data' => $resumen->toArray(),
            ];
        } catch (Throwable) {
            return [
                'disponible' => false,
                'mensaje' => 'Verificación de turnos no disponible.',
                'data' => null,
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function pago(TipoEntidadVigencia $tipo, int $id): array
    {
        $contract = $tipo === TipoEntidadVigencia::INSCRIPCION_SALA
            ? $this->pagoInscripcion
            : $this->pagoPaquete;

        if (! $contract->disponible()) {
            return [
                'disponible' => false,
                'mensaje' => 'Estado de pago no disponible.',
                'data' => null,
                'alerta' => null,
            ];
        }

        try {
            $estado = $tipo === TipoEntidadVigencia::INSCRIPCION_SALA
                ? $this->pagoInscripcion->estadoPago($id)
                : $this->pagoPaquete->estadoPago($id);
            $data = $estado->toArray();

            return [
                'disponible' => true,
                'mensaje' => null,
                'data' => $data,
                'alerta' => bccomp((string) $data['saldo'], '0.00', 2) > 0
                    ? 'Hay saldo pendiente. La operación no se bloquea.'
                    : null,
            ];
        } catch (Throwable) {
            return [
                'disponible' => false,
                'mensaje' => 'Estado de pago no disponible.',
                'data' => null,
                'alerta' => null,
            ];
        }
    }

    private function find(TipoEntidadVigencia $tipo, int $id): InscripcionSala|PaqueteSesiones
    {
        $modelo = $tipo === TipoEntidadVigencia::INSCRIPCION_SALA
            ? InscripcionSala::query()->find($id)
            : PaqueteSesiones::query()->find($id);

        if ($modelo === null) {
            throw new EntidadEstadoNotFoundException($tipo, $id);
        }

        return $modelo;
    }
}
