<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Rules\CancelacionMasivaRule;
use App\Modules\AgendaTurnos\Rules\RegeneracionRule;
use App\Modules\AgendaTurnos\Support\TurnoExistente;
use PHPUnit\Framework\TestCase;

/**
 * [Reprogramación y Cancelación] RF-011 / RN-009 / CA-007 / CU-03 FE-01.
 */
final class CancelacionMasivaRuleTest extends TestCase
{
    private CancelacionMasivaRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new CancelacionMasivaRule(new RegeneracionRule);
    }

    public function test_only_future_programmed_turns_are_eligible(): void
    {
        $clasificacion = $this->rule->evaluar([
            $this->turno(1, '2025-12-12', '09:00', EstadoTurno::PROGRAMADO),
            $this->turno(2, '2025-12-12', '10:00', EstadoTurno::ASISTIO),
            $this->turno(3, '2025-12-12', '11:00', EstadoTurno::FALTO),
            $this->turno(4, '2025-12-12', '12:00', EstadoTurno::CANCELADO),
            $this->turno(5, '2025-12-12', '13:00', EstadoTurno::REPROGRAMADO),
            $this->turno(6, '2025-12-01', '09:00', EstadoTurno::PROGRAMADO),
        ], '2025-12-10', '10:00');

        $this->assertSame([1], $clasificacion->idsElegibles());
        $this->assertSame(5, $clasificacion->cantidadExcluidos());
        $this->assertSame('El turno es pasado; 7.4 debe registrar su asistencia.', $clasificacion->excluidos[4]->motivo);
    }

    public function test_today_uses_the_strict_start_time(): void
    {
        $clasificacion = $this->rule->evaluar([
            $this->turno(1, '2025-12-10', '23:30', EstadoTurno::PROGRAMADO),
            $this->turno(2, '2025-12-10', '00:30', EstadoTurno::PROGRAMADO),
        ], '2025-12-10', '00:00');

        $this->assertSame([1, 2], $clasificacion->idsElegibles());

        $clasificacion = $this->rule->evaluar([
            $this->turno(3, '2025-12-10', '00:30', EstadoTurno::PROGRAMADO),
        ], '2025-12-10', '23:30');

        $this->assertSame([], $clasificacion->idsElegibles());
        $this->assertSame(1, $clasificacion->cantidadExcluidos());
    }

    private function turno(
        int $id,
        string $fecha,
        string $hora,
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
            horaInicio: $hora,
            horaFin: '09:50',
            estado: $estado,
        );
    }
}
