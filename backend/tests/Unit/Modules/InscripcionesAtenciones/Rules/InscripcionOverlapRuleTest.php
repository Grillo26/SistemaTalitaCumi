<?php

namespace Tests\Unit\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\InscripcionDuplicadaException;
use App\Modules\InscripcionesAtenciones\Rules\InscripcionOverlapRule;
use PHPUnit\Framework\TestCase;

final class InscripcionOverlapRuleTest extends TestCase
{
    public function test_accepts_adjacent_periods(): void
    {
        InscripcionOverlapRule::assertSinSolapamiento(
            [['id' => 1, 'fecha_inicio' => '2026-11-01', 'fecha_fin' => '2026-11-30']],
            '2026-12-01',
            '2026-12-31',
        );

        $this->assertTrue(true);
    }

    public function test_rejects_overlap_at_boundary(): void
    {
        $this->expectException(InscripcionDuplicadaException::class);

        InscripcionOverlapRule::assertSinSolapamiento(
            [['id' => 1, 'fecha_inicio' => '2026-11-01', 'fecha_fin' => '2026-11-30']],
            '2026-11-30',
            '2026-12-30',
        );
    }

    public function test_accepts_editing_same_inscription_when_excluded(): void
    {
        InscripcionOverlapRule::assertSinSolapamiento(
            [['id' => 7, 'fecha_inicio' => '2026-11-01', 'fecha_fin' => '2026-11-30']],
            '2026-11-01',
            '2026-12-15',
            7,
        );

        $this->assertTrue(true);
    }

    public function test_rejects_overlap_with_other_inscription_even_if_editing(): void
    {
        $this->expectException(InscripcionDuplicadaException::class);

        InscripcionOverlapRule::assertSinSolapamiento(
            [
                ['id' => 7, 'fecha_inicio' => '2026-11-01', 'fecha_fin' => '2026-11-30'],
                ['id' => 8, 'fecha_inicio' => '2026-11-15', 'fecha_fin' => '2026-12-15'],
            ],
            '2026-11-01',
            '2026-11-30',
            7,
        );
    }
}
