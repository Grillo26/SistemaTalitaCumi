<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Rules\RegeneracionRule;
use App\Modules\AgendaTurnos\Support\TurnoExistente;
use PHPUnit\Framework\TestCase;

/**
 * [Programación de Turnos] RF-010 / RF-011 / RN-006 / RT-004 / CA-007.
 */
final class RegeneracionRuleTest extends TestCase
{
    public function test_classifies_future_today_and_past_programmed_turns(): void
    {
        $turnos = [
            $this->turno(1, '2025-12-12', '09:00', EstadoTurno::PROGRAMADO),
            $this->turno(2, '2025-12-10', '11:00', EstadoTurno::PROGRAMADO),
            $this->turno(3, '2025-12-10', '09:00', EstadoTurno::PROGRAMADO),
            $this->turno(4, '2025-12-09', '09:00', EstadoTurno::PROGRAMADO),
        ];

        $clasificacion = (new RegeneracionRule)->evaluar($turnos, '2025-12-10', '10:00');

        $this->assertSame([1, 2], array_map(
            static fn (TurnoExistente $turno): int => $turno->id,
            $clasificacion->cancelar,
        ));
        $this->assertSame([3, 4], array_map(
            static fn (TurnoExistente $turno): int => $turno->id,
            $clasificacion->pasadosSinMarcar,
        ));
        $this->assertSame([3, 4], array_map(
            static fn (TurnoExistente $turno): int => $turno->id,
            $clasificacion->preservar,
        ));
    }

    public function test_attended_absent_cancelled_and_reprogrammed_are_preserved(): void
    {
        $turnos = [
            $this->turno(1, '2025-12-12', '09:00', EstadoTurno::ASISTIO),
            $this->turno(2, '2025-12-12', '10:00', EstadoTurno::FALTO),
            $this->turno(3, '2025-12-12', '11:00', EstadoTurno::CANCELADO),
            $this->turno(4, '2025-12-12', '12:00', EstadoTurno::REPROGRAMADO),
        ];

        $clasificacion = (new RegeneracionRule)->evaluar($turnos, '2025-12-10', '10:00');

        $this->assertSame([], $clasificacion->cancelar);
        $this->assertCount(4, $clasificacion->preservar);
        $this->assertSame(1, $clasificacion->cantidadAsistidos());
        $this->assertSame(1, $clasificacion->cantidadFaltados());
        $this->assertSame(0, $clasificacion->cantidadPasadosSinMarcar());
    }

    public function test_future_definition_uses_strict_start_time_on_today(): void
    {
        $rule = new RegeneracionRule;

        $this->assertTrue($rule->esFuturo($this->turno(1, '2025-12-10', '10:01', EstadoTurno::PROGRAMADO), '2025-12-10', '10:00'));
        $this->assertFalse($rule->esFuturo($this->turno(2, '2025-12-10', '10:00', EstadoTurno::PROGRAMADO), '2025-12-10', '10:00'));
        $this->assertFalse($rule->esFuturo($this->turno(3, '2025-12-10', '09:59', EstadoTurno::PROGRAMADO), '2025-12-10', '10:00'));
    }

    private function turno(
        int $id,
        string $fecha,
        string $horaInicio,
        EstadoTurno $estado,
    ): TurnoExistente {
        return new TurnoExistente(
            id: $id,
            sucursalId: 1,
            pacienteId: 1,
            profesionalId: null,
            salaId: null,
            servicioId: 10,
            fecha: $fecha,
            horaInicio: $horaInicio,
            horaFin: '09:50',
            estado: $estado,
            sesionKey: null,
        );
    }
}
