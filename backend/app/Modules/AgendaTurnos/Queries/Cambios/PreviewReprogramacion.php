<?php

namespace App\Modules\AgendaTurnos\Queries\Cambios;

use App\Models\User;
use App\Modules\AgendaTurnos\Exceptions\TurnoNotFoundException;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\DirectoriosAgenda;
use App\Modules\AgendaTurnos\Support\Hallazgo;
use App\Modules\AgendaTurnos\Support\ReprogramacionCalculator;
use App\Modules\AgendaTurnos\Support\ReprogramacionInput;

/**
 * [Reprogramación y Cancelación] RF-001 / RF-004 / RF-014 / CA-004.
 *
 * Previsualización sin persistir. La confirmación vuelve a calcular todo
 * bajo lock y con el estado esperado.
 */
final class PreviewReprogramacion
{
    public function __construct(
        private readonly AgendaAccessScope $access,
        private readonly ReprogramacionCalculator $calculator,
        private readonly DirectoriosAgenda $nombres,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(
        int $turnoId,
        ReprogramacionInput $input,
        User $actor,
    ): array {
        $turno = Turno::query()->find($turnoId);

        if ($turno === null) {
            throw new TurnoNotFoundException($turnoId);
        }

        $this->access->assertSucursal($actor, (int) $turno->sucursal_id);

        $calculada = $this->calculator->calcular($turno, $input, $actor);
        $nuevo = $calculada->nuevo;

        return [
            'turno' => [
                ...$calculada->original,
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
            'nuevo' => [
                ...$nuevo,
                'sala' => $nuevo['sala_id'] === null ? null : [
                    'id' => $nuevo['sala_id'],
                    'nombre' => $this->nombres->sala((int) $nuevo['sala_id']),
                ],
                'profesional' => $nuevo['profesional_id'] === null ? null : [
                    'id' => $nuevo['profesional_id'],
                    'nombre' => $this->nombres->profesional((int) $nuevo['profesional_id']),
                ],
            ],
            'motivo' => [
                'id' => $calculada->motivo->id,
                'codigo' => $calculada->motivo->codigo,
                'nombre' => $calculada->motivo->nombre,
                'origen' => $calculada->motivo->origen?->value,
                'descuenta_saldo' => $calculada->motivo->descuentaSaldo,
            ],
            'origen' => $calculada->origen,
            'hallazgos' => array_map(
                static fn (Hallazgo $hallazgo): array => $hallazgo->toArray(),
                $calculada->hallazgos,
            ),
            'advertencias' => $calculada->advertencias(),
            'puede_confirmar' => $calculada->puedeConfirmar,
            'requiere_confirmar_advertencias' => $calculada->requiereConfirmarAdvertencias,
        ];
    }
}
