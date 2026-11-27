<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Rules\ResumenSemanaRule;
use PHPUnit\Framework\TestCase;

final class ResumenSemanaRuleTest extends TestCase
{
    public function test_counts_all_states_including_reprogramado(): void
    {
        $resumen = (new ResumenSemanaRule)([
            EstadoTurno::PROGRAMADO,
            EstadoTurno::ASISTIO,
            EstadoTurno::ASISTIO,
            EstadoTurno::FALTO,
            EstadoTurno::CANCELADO,
            EstadoTurno::REPROGRAMADO,
        ]);

        $this->assertSame(6, $resumen->total);
        $this->assertSame(1, $resumen->porEstado['programado']);
        $this->assertSame(2, $resumen->porEstado['asistio']);
        $this->assertSame(1, $resumen->porEstado['falto']);
        $this->assertSame(1, $resumen->porEstado['cancelado']);
        $this->assertSame(1, $resumen->porEstado['reprogramado']);
        $this->assertSame('turnos_filtrados_visibles', $resumen->toArray()['base']);
    }

    public function test_empty_set_returns_zeroes_for_every_state(): void
    {
        $resumen = (new ResumenSemanaRule)([]);

        $this->assertSame(0, $resumen->total);
        $this->assertCount(5, $resumen->porEstado);
        $this->assertSame(0, $resumen->porEstado['asistio']);
    }

    public function test_unknown_values_are_ignored(): void
    {
        $resumen = (new ResumenSemanaRule)(['programado', 'desconocido']);

        $this->assertSame(1, $resumen->total);
        $this->assertSame(1, $resumen->porEstado['programado']);
    }
}
