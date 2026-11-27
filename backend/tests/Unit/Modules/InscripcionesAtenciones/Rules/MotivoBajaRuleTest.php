<?php

namespace Tests\Unit\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Enums\MotivoBaja;
use App\Modules\InscripcionesAtenciones\Exceptions\MotivoBajaInvalidoException;
use App\Modules\InscripcionesAtenciones\Rules\MotivoBajaRule;
use PHPUnit\Framework\TestCase;

final class MotivoBajaRuleTest extends TestCase
{
    public function test_accepts_closed_motive_without_detail(): void
    {
        MotivoBajaRule::assertValido(MotivoBaja::RETIRO_PACIENTE, null);

        $this->assertTrue(true);
    }

    public function test_other_motive_requires_detail(): void
    {
        $this->expectException(MotivoBajaInvalidoException::class);

        MotivoBajaRule::assertValido(MotivoBaja::OTRO, '   ');
    }

    public function test_detail_cannot_exceed_500_characters(): void
    {
        $this->expectException(MotivoBajaInvalidoException::class);

        MotivoBajaRule::assertValido(MotivoBaja::OTRO, str_repeat('a', 501));
    }
}
