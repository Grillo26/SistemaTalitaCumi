<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Rules\HorarioAgendaRule;
use PHPUnit\Framework\TestCase;

/**
 * 07.05/RF-002 / 07.05/RF-003 / 07.05/RN-003.
 */
final class HorarioAgendaRuleTest extends TestCase
{
    private HorarioAgendaRule $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new HorarioAgendaRule;
    }

    public function test_turno_dentro_del_horario_es_valido(): void
    {
        $this->assertSame([], $this->rule->errores('09:00', '09:50', '07:00', '19:00'));
    }

    public function test_turno_antes_de_apertura_es_invalido(): void
    {
        $errores = $this->rule->errores('06:30', '07:20', '07:00', '19:00');

        $this->assertCount(1, $errores);
        $this->assertStringContainsString('07:00', $errores[0]);
    }

    public function test_turno_despues_del_cierre_es_invalido(): void
    {
        $errores = $this->rule->errores('18:30', '19:30', '07:00', '19:00');

        $this->assertCount(1, $errores);
        $this->assertStringContainsString('19:00', $errores[0]);
    }

    public function test_hora_fin_menor_o_igual_es_invalida(): void
    {
        $this->assertNotSame([], $this->rule->errores('10:00', '10:00', '07:00', '19:00'));
    }

    public function test_configuracion_invertida_es_invalida(): void
    {
        $this->assertNotSame([], $this->rule->errores('09:00', '09:50', '19:00', '07:00'));
    }
}
