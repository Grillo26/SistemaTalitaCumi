<?php

namespace Tests\Unit\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Support\SaldoAsistenciaResolver;
use App\Modules\InscripcionesAtenciones\Contracts\ConsumoPaqueteContract;
use App\Modules\InscripcionesAtenciones\Contracts\ConsumoPaqueteResumen;
use PHPUnit\Framework\TestCase;

/**
 * 07.04/RF-002, 07.04/RF-013, 07.04/RN-009.
 */
final class SaldoAsistenciaResolverTest extends TestCase
{
    public function test_package_without_balance_warns_but_does_not_block(): void
    {
        $resolver = new SaldoAsistenciaResolver(new class implements ConsumoPaqueteContract
        {
            public function disponible(): bool
            {
                return true;
            }

            public function resumen(int $paqueteId): ConsumoPaqueteResumen
            {
                return new ConsumoPaqueteResumen(1, 1, 0, 0);
            }
        });

        $turno = new Turno;
        $turno->forceFill(['paquete_sesiones_id' => 1]);

        $resultado = $resolver->paraTurno($turno, false);

        $this->assertTrue($resultado['aplicable']);
        $this->assertTrue($resultado['disponible']);
        $this->assertSame(0, $resultado['saldo_actual']);
        $this->assertSame(0, $resultado['saldo_resultante']);
        $this->assertTrue($resultado['saldo_agotado']);
        $this->assertStringContainsString('Saldo agotado', $resultado['mensaje']);
    }

    public function test_turn_without_package_is_not_applicable(): void
    {
        $resolver = new SaldoAsistenciaResolver(new class implements ConsumoPaqueteContract
        {
            public function disponible(): bool
            {
                return true;
            }

            public function resumen(int $paqueteId): ConsumoPaqueteResumen
            {
                return new ConsumoPaqueteResumen(0, 0, 0, 0);
            }
        });

        $turno = new Turno;
        $turno->forceFill(['paquete_sesiones_id' => null]);

        $resultado = $resolver->paraTurno($turno, true);

        $this->assertFalse($resultado['aplicable']);
        $this->assertFalse($resultado['disponible']);
    }
}
