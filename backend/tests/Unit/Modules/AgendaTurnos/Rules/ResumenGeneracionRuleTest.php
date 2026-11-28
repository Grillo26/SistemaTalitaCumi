<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\SeveridadHallazgo;
use App\Modules\AgendaTurnos\Rules\ResumenGeneracionRule;
use App\Modules\AgendaTurnos\Support\FilaPreview;
use App\Modules\AgendaTurnos\Support\Hallazgo;
use PHPUnit\Framework\TestCase;

/**
 * [Programación de Turnos] RF-014 / RN-007.
 */
final class ResumenGeneracionRuleTest extends TestCase
{
    public function test_counts_rows_and_blocks_confirmation_on_blocking_findings(): void
    {
        $filas = [
            new FilaPreview('2025-12-02', '09:00', '09:50', 'generable'),
            $this->filaConHallazgo('2025-12-04', SeveridadHallazgo::BLOQUEO),
            $this->filaConHallazgo('2025-12-09', SeveridadHallazgo::ADVERTENCIA),
            new FilaPreview('2025-12-11', '09:00', '09:50', 'feriado', generable: false),
            new FilaPreview('2025-12-16', '09:00', '09:50', 'pasada', generable: false),
            new FilaPreview('2025-12-18', '09:00', '09:50', 'omitida', generable: false),
        ];

        $resumen = (new ResumenGeneracionRule)->resumir($filas, false);

        $this->assertSame(2, $resumen->generables);
        $this->assertSame(1, $resumen->bloqueados);
        $this->assertSame(1, $resumen->advertencias);
        $this->assertSame(1, $resumen->feriados);
        $this->assertSame(1, $resumen->pasadas);
        $this->assertSame(1, $resumen->omitidas);
        $this->assertFalse($resumen->puedeConfirmar);
        $this->assertTrue($resumen->requiereConfirmarAdvertencias);
    }

    public function test_warnings_require_explicit_confirmation(): void
    {
        $filas = [
            new FilaPreview('2025-12-02', '09:00', '09:50', 'generable'),
            $this->filaConHallazgo('2025-12-04', SeveridadHallazgo::ADVERTENCIA),
        ];

        $rule = new ResumenGeneracionRule;

        $this->assertFalse($rule->resumir($filas, false)->puedeConfirmar);
        $this->assertTrue($rule->resumir($filas, true)->puedeConfirmar);
    }

    public function test_missing_sessions_also_require_confirmation(): void
    {
        $filas = [new FilaPreview('2025-12-02', '09:00', '09:50', 'generable')];
        $rule = new ResumenGeneracionRule;

        $resumen = $rule->resumir($filas, false, faltantes: 2);

        $this->assertSame(2, $resumen->faltantes);
        $this->assertTrue($resumen->requiereConfirmarAdvertencias);
        $this->assertFalse($resumen->puedeConfirmar);
        $this->assertTrue($rule->resumir($filas, true, faltantes: 2)->puedeConfirmar);
    }

    public function test_zero_generable_rows_cannot_be_confirmed(): void
    {
        $resumen = (new ResumenGeneracionRule)->resumir([], true);

        $this->assertFalse($resumen->puedeConfirmar);
        $this->assertSame(0, $resumen->generables);
    }

    private function filaConHallazgo(string $fecha, SeveridadHallazgo $severidad): FilaPreview
    {
        $fila = new FilaPreview($fecha, '09:00', '09:50', 'generable');
        $fila->agregarHallazgo(new Hallazgo(
            tipo: 'prueba',
            severidad: $severidad,
            mensaje: 'Hallazgo de prueba',
            fecha: $fecha,
        ));

        return $fila;
    }
}
