<?php

namespace Tests\Unit\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\MontoEsperadoInvalidoException;
use App\Modules\InscripcionesAtenciones\Exceptions\ObservacionesInvalidasException;
use App\Modules\InscripcionesAtenciones\Rules\InscripcionObservacionesRule;
use App\Modules\InscripcionesAtenciones\Rules\MontoEsperadoRule;
use PHPUnit\Framework\TestCase;

final class MoneyAndObservationsRuleTest extends TestCase
{
    public function test_accepts_exact_money_string(): void
    {
        MontoEsperadoRule::assertValido('500.00');
        MontoEsperadoRule::assertValido('0.00');

        $this->assertTrue(true);
    }

    public function test_rejects_float_like_or_invalid_money(): void
    {
        $this->expectException(MontoEsperadoInvalidoException::class);

        MontoEsperadoRule::assertValido('500.001');
    }

    public function test_accepts_observations_with_500_characters(): void
    {
        InscripcionObservacionesRule::assertValidas(str_repeat('a', 500));

        $this->assertTrue(true);
    }

    public function test_rejects_observations_over_500_characters(): void
    {
        $this->expectException(ObservacionesInvalidasException::class);

        InscripcionObservacionesRule::assertValidas(str_repeat('a', 501));
    }
}
