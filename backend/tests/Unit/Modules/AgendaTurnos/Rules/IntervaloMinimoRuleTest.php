<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Rules\IntervaloMinimoRule;
use App\Modules\AgendaTurnos\Support\TurnoExistente;
use PHPUnit\Framework\TestCase;

/**
 * 07.05/RF-002 / 07.05/RF-003 / 07.05/RN-003.
 */
final class IntervaloMinimoRuleTest extends TestCase
{
    private IntervaloMinimoRule $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new IntervaloMinimoRule;
    }

    public function test_intervalo_cero_no_bloquea(): void
    {
        $this->assertSame([], $this->rule->errores(1, '2025-12-02', '09:00', '09:50', [
            $this->turno(1, '08:50', '09:00'),
        ], 0));
    }

    public function test_separacion_menor_al_minimo_bloquea(): void
    {
        $errores = $this->rule->errores(1, '2025-12-02', '09:00', '09:50', [
            $this->turno(1, '08:30', '08:50'),
        ], 15);

        $this->assertCount(1, $errores);
        $this->assertStringContainsString('15', $errores[0]);
    }

    public function test_separacion_igual_al_minimo_es_valida(): void
    {
        $this->assertSame([], $this->rule->errores(1, '2025-12-02', '09:00', '09:50', [
            $this->turno(1, '08:30', '08:45'),
        ], 15));
    }

    public function test_otro_profesional_no_cuenta(): void
    {
        $this->assertSame([], $this->rule->errores(1, '2025-12-02', '09:00', '09:50', [
            $this->turno(2, '08:30', '08:50'),
        ], 15));
    }

    private function turno(int $profesionalId, string $inicio, string $fin): TurnoExistente
    {
        return new TurnoExistente(
            id: 10 + $profesionalId,
            sucursalId: 1,
            pacienteId: 5,
            profesionalId: $profesionalId,
            salaId: null,
            servicioId: 7,
            fecha: '2025-12-02',
            horaInicio: $inicio,
            horaFin: $fin,
            estado: EstadoTurno::PROGRAMADO,
        );
    }
}
