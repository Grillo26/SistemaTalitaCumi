<?php

namespace App\Modules\AgendaTurnos\Queries\Cambios;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Exceptions\TurnoNotFoundException;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\CancelacionCalculator;
use App\Modules\AgendaTurnos\Support\DirectoriosAgenda;

/**
 * [Reprogramación y Cancelación] RF-002 / RF-008 / RF-014 / CA-005.
 */
final class PreviewCancelacion
{
    public function __construct(
        private readonly AgendaAccessScope $access,
        private readonly CancelacionCalculator $calculator,
        private readonly DirectoriosAgenda $nombres,
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
    ): array {
        $turno = Turno::query()->find($turnoId);

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

        return [
            'turno' => [
                ...$calculada->turnoData,
                'paciente' => [
                    'id' => (int) $turno->paciente_id,
                    'nombre' => $this->nombres->paciente((int) $turno->paciente_id),
                ],
                'servicio' => [
                    'id' => (int) $turno->servicio_id,
                    'nombre' => $this->nombres->servicio((int) $turno->servicio_id),
                ],
                'sala' => $turno->sala_id === null ? null : [
                    'id' => (int) $turno->sala_id,
                    'nombre' => $this->nombres->sala((int) $turno->sala_id),
                ],
                'profesional' => $turno->profesional_id === null ? null : [
                    'id' => (int) $turno->profesional_id,
                    'nombre' => $this->nombres->profesional((int) $turno->profesional_id),
                ],
            ],
            'motivo' => [
                'id' => $calculada->motivo->id,
                'codigo' => $calculada->motivo->codigo,
                'nombre' => $calculada->motivo->nombre,
                'origen' => $calculada->motivo->origen?->value,
                'descuenta_saldo' => $calculada->motivo->descuentaSaldo,
            ],
            'consume_saldo' => $calculada->consumeSaldo,
            'saldo' => $calculada->saldo,
            'puede_confirmar' => true,
        ];
    }
}
