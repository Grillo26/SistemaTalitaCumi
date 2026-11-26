<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\InscripcionesAtenciones\Support\VigenciaPeriod;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Tests\TestCase;

final class VigenciaTimezoneTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_today_uses_la_paz_across_midnight(): void
    {
        config(['app.timezone' => 'America/La_Paz']);

        Carbon::setTestNow(Carbon::parse('2026-11-27 03:30:00', 'UTC'));
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-27 03:30:00', 'UTC'));

        $this->assertSame('2026-11-26', VigenciaPeriod::today());
    }
}
