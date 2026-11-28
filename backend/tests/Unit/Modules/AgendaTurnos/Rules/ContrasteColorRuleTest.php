<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Rules\ContrasteColorRule;
use PHPUnit\Framework\TestCase;

final class ContrasteColorRuleTest extends TestCase
{
    public function test_light_color_uses_dark_text(): void
    {
        $this->assertSame('#101828', (new ContrasteColorRule)('#FFFFFF'));
        $this->assertSame('#101828', (new ContrasteColorRule)('#F9733F'));
    }

    public function test_dark_color_uses_white_text(): void
    {
        $this->assertSame('#FFFFFF', (new ContrasteColorRule)('#000000'));
        $this->assertSame('#FFFFFF', (new ContrasteColorRule)('#671C61'));
    }

    public function test_accepts_three_digit_hex(): void
    {
        $this->assertSame('#101828', (new ContrasteColorRule)('#fff'));
        $this->assertSame('#FFFFFF', (new ContrasteColorRule)('#000'));
    }

    public function test_invalid_color_returns_null(): void
    {
        $this->assertNull((new ContrasteColorRule)(null));
        $this->assertNull((new ContrasteColorRule)('rojo'));
        $this->assertNull((new ContrasteColorRule)('#GGGGGG'));
    }
}
