<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Rules\RangoIndicadoresRule;
use PHPUnit\Framework\TestCase;

/**
 * 07.04/CU-03 FE-01.
 */
final class RangoIndicadoresRuleTest extends TestCase
{
    public function test_valid_range(): void
    {
        $this->assertSame([], (new RangoIndicadoresRule)->errores('2025-11-01', '2025-11-30'));
    }

    public function test_invalid_or_inverted_range(): void
    {
        $rule = new RangoIndicadoresRule;

        $this->assertNotSame([], $rule->errores('2025-11-30', '2025-11-01'));
        $this->assertNotSame([], $rule->errores('2025-02-30', '2025-03-01'));
    }

    public function test_range_is_capped(): void
    {
        $this->assertNotSame([], (new RangoIndicadoresRule)->errores('2024-01-01', '2025-12-31'));
    }
}
