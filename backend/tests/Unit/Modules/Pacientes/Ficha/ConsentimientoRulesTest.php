<?php

namespace Tests\Unit\Modules\Pacientes\Ficha;

use App\Modules\Pacientes\Exceptions\ConsentimientoDateInvalidException;
use App\Modules\Pacientes\Rules\ConsentimientoDateRule;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class ConsentimientoRulesTest extends TestCase
{
    public function test_consent_date_cannot_be_future(): void
    {
        $rule = new ConsentimientoDateRule;
        $today = CarbonImmutable::parse('2026-11-21');

        $rule->assertNotFuture($today, $today);
        $rule->assertNotFuture($today->subDay(), $today);
        $this->addToAssertionCount(2);

        $this->expectException(ConsentimientoDateInvalidException::class);
        $rule->assertNotFuture($today->addDay(), $today);
    }
}
