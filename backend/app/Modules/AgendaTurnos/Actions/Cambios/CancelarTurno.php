<?php

namespace App\Modules\AgendaTurnos\Actions\Cambios;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Events\TurnoCancelado;
use App\Modules\AgendaTurnos\Exceptions\TurnoNotFoundException;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\CancelacionCalculator;
use App\Modules\AgendaTurnos\Support\CancelaTurno;
use App\Modules\AgendaTurnos\Support\DiagnosticoEstadoSincronizador;
use Illuminate\Support\Facades\DB;

/**
 * [Reprogramación y Cancelación] RF-002 / RF-003 / RF-007 / RF-008 /
 * RF-010 / RF-013 / RF-014 / RN-003 / RN-006 / CA-002 / CA-003 / CA-005.
 */
final class CancelarTurno
{
    public function __construct(
        private readonly AgendaAccessScope $access,
        private readonly CancelacionCalculator $calculator,
        private readonly CancelaTurno $cancelacion,
        private readonly DiagnosticoEstadoSincronizador $diagnosticos,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(
        int $turnoId,
        int $motivoId,
        ?string $motivoTexto,
        EstadoTurno $estadoEsperado,
        User $actor,
        ?string $ip = null,
    ): array {
        return DB::transaction(function () use (
            $turnoId,
            $motivoId,
            $motivoTexto,
            $estadoEsperado,
            $actor,
            $ip,
        ): array {
            $turno = Turno::query()->whereKey($turnoId)->lockForUpdate()->first();

            if ($turno === null) {
                throw new TurnoNotFoundException($turnoId);
            }

            $this->access->assertSucursal($actor, (int) $turno->sucursal_id);

            $calculada = $this->calculator->calcular(
                $turno,
                $motivoId,
                $motivoTexto,
                $estadoEsperado,
            );

            $this->cancelacion->aplicar(
                $turno,
                $calculada->motivo,
                $motivoTexto,
                $calculada->consumeSaldo,
                $actor,
                null,
                false,
            );

            if ($turno->atencion_diagnostico_id !== null) {
                $this->diagnosticos->sincronizar(
                    (int) $turno->atencion_diagnostico_id,
                    $actor,
                    $ip,
                );
            }

            event(new TurnoCancelado(
                turnoId: (int) $turno->id,
                sucursalId: (int) $turno->sucursal_id,
                pacienteId: (int) $turno->paciente_id,
                fecha: $turno->fecha->toDateString(),
                horaInicio: (string) $turno->hora_inicio,
                horaFin: (string) $turno->hora_fin,
                motivo: $calculada->motivo,
                consumeSaldo: $calculada->consumeSaldo,
                advertencias: [],
                actorId: (int) $actor->id,
                ip: $ip,
                motivoTexto: $motivoTexto,
            ));

            return [
                'turno' => [
                    ...$calculada->turnoData,
                    'estado' => 'cancelado',
                    'estado_label' => 'Cancelado',
                    'consume_saldo' => $calculada->consumeSaldo,
                ],
                'motivo' => [
                    'id' => $calculada->motivo->id,
                    'codigo' => $calculada->motivo->codigo,
                    'nombre' => $calculada->motivo->nombre,
                    'origen' => $calculada->motivo->origen?->value,
                    'descuenta_saldo' => $calculada->motivo->descuentaSaldo,
                ],
                'saldo' => $calculada->saldo,
            ];
        });
    }
}
