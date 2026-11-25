<?php

namespace Tests\Unit\Modules\Pacientes\Ficha;

use App\Modules\Pacientes\Exceptions\ObservacionDateInvalidException;
use App\Modules\Pacientes\Rules\ObservacionDateRule;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class ObservacionRulesTest extends TestCase
{
    public function test_observation_date_cannot_be_future(): void
    {
        $rule = new ObservacionDateRule;
        $today = CarbonImmutable::parse('2026-11-21');

        $rule->assertNotFuture($today, $today);
        $rule->assertNotFuture($today->subDay(), $today);
        $this->addToAssertionCount(2);

        $this->expectException(ObservacionDateInvalidException::class);
        $rule->assertNotFuture($today->addDay(), $today);
    }
}
