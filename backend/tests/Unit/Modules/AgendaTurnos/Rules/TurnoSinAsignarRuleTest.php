<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Rules\TurnoSinAsignarRule;
use PHPUnit\Framework\TestCase;

final class TurnoSinAsignarRuleTest extends TestCase
{
    public function test_null_professional_is_unassigned(): void
    {
        $this->assertTrue((new TurnoSinAsignarRule)(null));
    }

    public function test_any_professional_id_is_assigned(): void
    {
        $this->assertFalse((new TurnoSinAsignarRule)(1));
        $this->assertFalse((new TurnoSinAsignarRule)(999));
    }
}
