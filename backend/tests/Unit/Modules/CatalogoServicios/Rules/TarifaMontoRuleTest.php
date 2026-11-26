<?php

namespace Tests\Unit\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\TarifaMontoInvalidoException;
use App\Modules\CatalogoServicios\Rules\TarifaMontoRule;
use PHPUnit\Framework\TestCase;

final class TarifaMontoRuleTest extends TestCase
{
    public function test_normalizes_amounts_with_two_decimals(): void
    {
        $this->assertSame('0.00', TarifaMontoRule::normalizeAndValidate('0'));
        $this->assertSame('100.00', TarifaMontoRule::normalizeAndValidate('100'));
        $this->assertSame('100.50', TarifaMontoRule::normalizeAndValidate('100.5'));
        $this->assertSame('100.50', TarifaMontoRule::normalizeAndValidate('100.50'));
        $this->assertSame('0.00', TarifaMontoRule::normalizeAndValidate('000.00'));
    }

    public function test_rejects_invalid_amounts(): void
    {
        foreach (['-1', '1.234', '1,50', 'abc', ''] as $amount) {
            try {
                TarifaMontoRule::normalizeAndValidate($amount);
                $this->fail('Se esperaba TarifaMontoInvalidoException para '.$amount);
            } catch (TarifaMontoInvalidoException) {
                $this->assertTrue(true);
            }
        }
    }
}
