<?php

namespace Tests\Unit\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Rules\PaqueteEstimacionRule;
use App\Modules\InscripcionesAtenciones\Support\PaquetePeriod;
use PHPUnit\Framework\TestCase;

final class PaqueteEstimacionRuleTest extends TestCase
{
    public function test_counts_selected_weekdays_inside_inclusive_period(): void
    {
        // Lunes 2 al domingo 8 de noviembre de 2026: lunes, miércoles y viernes.
        $this->assertSame(3, PaqueteEstimacionRule::estimar([1, 3, 5], '2026-11-02', '2026-11-08'));
    }

    public function test_returns_zero_without_valid_days(): void
    {
        $this->assertSame(0, PaqueteEstimacionRule::estimar([], '2026-11-02', '2026-11-08'));
        $this->assertSame(0, PaqueteEstimacionRule::estimar([7, 9], '2026-11-02', '2026-11-08'));
    }

    public function test_counts_each_selected_day_once_for_single_day_period(): void
    {
        $this->assertSame(1, PaqueteEstimacionRule::estimar([2], '2026-11-03', '2026-11-03'));
        $this->assertSame(0, PaqueteEstimacionRule::estimar([1], '2026-11-03', '2026-11-03'));
    }

    public function test_does_not_warn_when_estimate_matches_quantity(): void
    {
        $this->assertNull(PaqueteEstimacionRule::advertencia(3, 3));
    }

    public function test_warns_when_estimate_is_below_quantity(): void
    {
        $advertencia = PaqueteEstimacionRule::advertencia(10, 3);

        $this->assertNotNull($advertencia);
        $this->assertStringContainsString('solo alcanza para 3', $advertencia);
    }

    public function test_warns_when_estimate_exceeds_quantity(): void
    {
        $advertencia = PaqueteEstimacionRule::advertencia(3, 10);

        $this->assertNotNull($advertencia);
        $this->assertStringContainsString('alcanza para 10', $advertencia);
    }

    public function test_period_helpers_calculate_next_start_and_same_duration(): void
    {
        $this->assertSame('2026-12-26', PaquetePeriod::nextStart('2026-12-25'));
        $this->assertSame(
            '2027-01-24',
            PaquetePeriod::sameDurationEnd('2026-12-26', '2026-11-26', '2026-12-25'),
        );
        $this->assertSame(
            '2026-12-30',
            PaquetePeriod::sameDurationEnd('2026-12-01', '2026-11-01', '2026-11-30'),
        );
    }
}
