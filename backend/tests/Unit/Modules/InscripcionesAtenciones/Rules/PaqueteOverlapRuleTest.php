<?php

namespace Tests\Unit\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\PaqueteDuplicadoException;
use App\Modules\InscripcionesAtenciones\Rules\PaqueteOverlapRule;
use PHPUnit\Framework\TestCase;

final class PaqueteOverlapRuleTest extends TestCase
{
    public function test_accepts_adjacent_periods(): void
    {
        PaqueteOverlapRule::assertSinSolapamiento(
            [['id' => 1, 'fecha_inicio' => '2026-11-01', 'fecha_fin' => '2026-11-30']],
            '2026-12-01',
            '2026-12-31',
        );

        $this->assertTrue(true);
    }

    public function test_rejects_overlap_at_boundary(): void
    {
        $this->expectException(PaqueteDuplicadoException::class);

        PaqueteOverlapRule::assertSinSolapamiento(
            [['id' => 1, 'fecha_inicio' => '2026-11-01', 'fecha_fin' => '2026-11-30']],
            '2026-11-30',
            '2026-12-30',
        );
    }

    public function test_rejects_contained_period(): void
    {
        $this->expectException(PaqueteDuplicadoException::class);

        PaqueteOverlapRule::assertSinSolapamiento(
            [['id' => 1, 'fecha_inicio' => '2026-11-01', 'fecha_fin' => '2026-11-30']],
            '2026-11-10',
            '2026-11-20',
        );
    }

    public function test_excludes_the_package_being_edited(): void
    {
        PaqueteOverlapRule::assertSinSolapamiento(
            [['id' => 7, 'fecha_inicio' => '2026-11-01', 'fecha_fin' => '2026-11-30']],
            '2026-11-01',
            '2026-12-15',
            7,
        );

        $this->assertTrue(true);
    }

    public function test_reports_conflict_data(): void
    {
        try {
            PaqueteOverlapRule::assertSinSolapamiento(
                [['id' => 9, 'fecha_inicio' => '2026-11-01', 'fecha_fin' => '2026-11-30']],
                '2026-11-15',
                '2026-12-15',
            );
            $this->fail('Se esperaba PaqueteDuplicadoException.');
        } catch (PaqueteDuplicadoException $exception) {
            $this->assertSame(9, $exception->conflict['paquete_id']);
        }
    }

    public function test_accepts_an_empty_existing_period_list(): void
    {
        PaqueteOverlapRule::assertSinSolapamiento([], '2026-11-01', '2026-11-30');

        $this->assertTrue(true);
    }
}
