<?php

namespace Tests\Unit\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\TarifaSolapadaException;
use App\Modules\CatalogoServicios\Rules\TarifaOverlapRule;
use PHPUnit\Framework\TestCase;

final class TarifaOverlapRuleTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function range(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => '2026-12-31',
            'monto' => '100.00',
            'sucursal' => 'Global',
            'categoria' => 'General',
        ], $overrides);
    }

    public function test_overlapping_ranges_are_rejected(): void
    {
        $this->expectException(TarifaSolapadaException::class);
        TarifaOverlapRule::assertNoOverlap(
            '2026-06-01',
            '2027-03-31',
            [$this->range()],
        );
    }

    public function test_contiguous_ranges_do_not_overlap(): void
    {
        TarifaOverlapRule::assertNoOverlap(
            '2027-01-01',
            null,
            [$this->range()],
        );

        $this->assertTrue(true);
    }

    public function test_open_ended_existing_range_blocks_any_future_range(): void
    {
        $this->expectException(TarifaSolapadaException::class);
        TarifaOverlapRule::assertNoOverlap(
            '2030-01-01',
            '2030-12-31',
            [$this->range(['vigente_hasta' => null])],
        );
    }

    public function test_open_ended_candidate_blocks_existing_future_range(): void
    {
        $this->expectException(TarifaSolapadaException::class);
        TarifaOverlapRule::assertNoOverlap(
            '2025-01-01',
            null,
            [$this->range()],
        );
    }

    public function test_no_ranges_is_valid(): void
    {
        TarifaOverlapRule::assertNoOverlap('2026-01-01', '2026-12-31', []);

        $this->assertTrue(true);
    }
}
