<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Exceptions\EstadoTurnoDesactualizadoException;
use App\Modules\AgendaTurnos\Exceptions\MotivoObligatorioException;
use App\Modules\AgendaTurnos\Exceptions\TurnoNoModificableException;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Rules\EfectoSaldoRule;
use App\Modules\AgendaTurnos\Rules\EstadoEsperadoRule;
use App\Modules\AgendaTurnos\Rules\MotivoObligatorioRule;
use App\Modules\AgendaTurnos\Rules\RegeneracionRule;
use App\Modules\AgendaTurnos\Rules\TurnoModificableRule;

/**
 * Cálculo compartido por el preview y la confirmación de cancelación.
 */
final class CancelacionCalculator
{
    public function __construct(
        private readonly MotivoTurnoResolver $motivos,
        private readonly TurnoModificableRule $modificable,
        private readonly EstadoEsperadoRule $estadoEsperado,
        private readonly MotivoObligatorioRule $motivoRule,
        private readonly RegeneracionRule $futuro,
        private readonly EfectoSaldoRule $efecto,
        private readonly SaldoPaqueteResolver $saldo,
        private readonly AgendaClock $clock,
    ) {}

    public function calcular(
        Turno $turno,
        int $motivoId,
        ?string $motivoTexto,
        EstadoTurno $estadoEsperado,
    ): CancelacionCalculada {
        if (! $this->modificable->permite($turno->estado)) {
            throw new TurnoNoModificableException(
                $turno->estado,
                $this->modificable->mensaje($turno->estado, 'cancelar')
                    ?? 'El turno no se puede cancelar.',
            );
        }

        if (! $this->estadoEsperado->coincide($turno->estado, $estadoEsperado)) {
            throw new EstadoTurnoDesactualizadoException($turno->estado);
        }

        $existente = TurnoExistente::fromTurno($turno);

        if (! $this->futuro->esFuturo($existente, $this->clock->hoy(), $this->clock->horaActual())) {
            throw new TurnoNoModificableException(
                $turno->estado,
                'El turno es pasado; 7.4 debe registrar su asistencia.',
            );
        }

        $motivo = $this->motivos->obtener($motivoId);
        $errores = $this->motivoRule->errores($motivo, 'cancelar', $motivoTexto);

        if ($errores !== [] || $motivo === null) {
            throw new MotivoObligatorioException($errores);
        }

        $consume = $this->efecto->consume($motivo->descuentaSaldo);

        return new CancelacionCalculada(
            modelo: $turno,
            motivo: $motivo,
            consumeSaldo: $consume,
            turnoData: [
                'id' => (int) $turno->id,
                'fecha' => $turno->fecha->toDateString(),
                'hora_inicio' => (string) $turno->hora_inicio,
                'hora_fin' => (string) $turno->hora_fin,
                'estado' => $turno->estado->value,
                'estado_label' => $turno->estado->label(),
                'paciente_id' => (int) $turno->paciente_id,
                'sucursal_id' => (int) $turno->sucursal_id,
                'paquete_sesiones_id' => $turno->paquete_sesiones_id === null
                    ? null
                    : (int) $turno->paquete_sesiones_id,
            ],
            saldo: $this->saldo->paraPaquete(
                $turno->paquete_sesiones_id === null ? null : (int) $turno->paquete_sesiones_id,
                $consume,
            ),
        );
    }
}
