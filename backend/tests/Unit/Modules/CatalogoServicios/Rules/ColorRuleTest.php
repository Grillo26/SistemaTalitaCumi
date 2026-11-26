<?php

namespace Tests\Unit\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\ColorInvalidoException;
use App\Modules\CatalogoServicios\Rules\ColorRule;
use PHPUnit\Framework\TestCase;

final class ColorRuleTest extends TestCase
{
    public function test_accepts_hex_6_and_3_digits_and_normalizes_uppercase(): void
    {
        $this->assertSame('#671C61', ColorRule::normalizeAndValidate('#671c61'));
        $this->assertSame('#ABC', ColorRule::normalizeAndValidate(' #abc '));
    }

    public function test_rejects_invalid_colors(): void
    {
        foreach (['671c61', '#GGGGGG', '#12345', '#1234567', ''] as $color) {
            try {
                ColorRule::normalizeAndValidate($color);
                $this->fail('Se esperaba ColorInvalidoException para '.$color);
            } catch (ColorInvalidoException) {
                $this->assertTrue(true);
            }
        }
    }
}
