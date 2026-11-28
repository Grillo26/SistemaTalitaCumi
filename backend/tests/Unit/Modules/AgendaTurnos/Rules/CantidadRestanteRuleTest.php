<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Rules\CantidadRestanteRule;
use App\Modules\AgendaTurnos\Support\TurnoExistente;
use PHPUnit\Framework\TestCase;

/**
 * [Programación de Turnos] RF-010 / RN-006 / CA-007.
 */
final class CantidadRestanteRuleTest extends TestCase
{
    public function test_attended_and_absent_consume_sessions_but_cancelled_do_not(): void
    {
        $turnos = [
            $this->turno(1, EstadoTurno::ASISTIO),
            $this->turno(2, EstadoTurno::ASISTIO),
            $this->turno(3, EstadoTurno::FALTO),
            $this->turno(4, EstadoTurno::CANCELADO),
            $this->turno(5, EstadoTurno::CANCELADO),
            $this->turno(6, EstadoTurno::PROGRAMADO),
        ];

        $this->assertSame(7, (new CantidadRestanteRule)->calcular(10, $turnos));
    }

    public function test_never_returns_a_negative_number(): void
    {
        $turnos = [
            $this->turno(1, EstadoTurno::ASISTIO),
            $this->turno(2, EstadoTurno::FALTO),
        ];

        $this->assertSame(0, (new CantidadRestanteRule)->calcular(1, $turnos));
    }

    public function test_cancelled_with_consume_saldo_consumes_the_session(): void
    {
        $turnos = [
            $this->turno(1, EstadoTurno::ASISTIO),
            $this->turno(2, EstadoTurno::CANCELADO, consume: true),
            $this->turno(3, EstadoTurno::CANCELADO),
        ];

        $this->assertSame(7, (new CantidadRestanteRule)->calcular(9, $turnos));
    }

    public function test_faltantes_only_returns_the_missing_amount(): void
    {
        $rule = new CantidadRestanteRule;

        $this->assertSame(3, $rule->faltantes(10, 7));
        $this->assertSame(0, $rule->faltantes(10, 10));
        $this->assertSame(0, $rule->faltantes(10, 12));
    }

    private function turno(int $id, EstadoTurno $estado, bool $consume = false): TurnoExistente
    {
        return new TurnoExistente(
            id: $id,
            sucursalId: 1,
            pacienteId: 1,
            profesionalId: null,
            salaId: null,
            servicioId: 10,
            fecha: '2025-12-02',
            horaInicio: '09:00',
            horaFin: '09:50',
            estado: $estado,
            sesionKey: null,
            turnoOrigenId: null,
            consumeSaldo: $consume,
        );
    }
}
