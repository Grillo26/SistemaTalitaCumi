<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Rules\MotivoAsistenciaRule;
use PHPUnit\Framework\TestCase;

/**
 * 07.04/RF-005, 07.04/RN-006.
 */
final class MotivoAsistenciaRuleTest extends TestCase
{
    public function test_outside_window_requires_motive(): void
    {
        $rule = new MotivoAsistenciaRule;

        $this->assertNotSame([], $rule->errores(true, null));
        $this->assertNotSame([], $rule->errores(true, '   '));
        $this->assertSame([], $rule->errores(true, 'Error de carga'));
    }

    public function test_inside_window_is_optional(): void
    {
        $this->assertSame([], (new MotivoAsistenciaRule)->errores(false, null));
    }

    public function test_length_is_limited(): void
    {
        $this->assertNotSame([], (new MotivoAsistenciaRule)->errores(true, str_repeat('a', 501)));
    }
}
