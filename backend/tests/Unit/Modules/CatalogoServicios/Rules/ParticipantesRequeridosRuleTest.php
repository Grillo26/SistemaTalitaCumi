<?php

namespace Tests\Unit\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Rules\ParticipantesRequeridosRule;
use PHPUnit\Framework\TestCase;

final class ParticipantesRequeridosRuleTest extends TestCase
{
    public function test_returns_missing_quantities_by_specialty(): void
    {
        $faltantes = ParticipantesRequeridosRule::faltantes(
            [
                ['especialidad_id' => 1, 'cantidad_minima' => 2],
                ['especialidad_id' => 2, 'cantidad_minima' => 1],
            ],
            [
                ['especialidad_id' => 1, 'cantidad' => 1],
                ['especialidad_id' => 3, 'cantidad' => 4],
            ],
        );

        $this->assertSame([
            ['especialidad_id' => 1, 'requeridos' => 2, 'presentes' => 1, 'faltan' => 1],
            ['especialidad_id' => 2, 'requeridos' => 1, 'presentes' => 0, 'faltan' => 1],
        ], $faltantes);
    }

    public function test_returns_empty_when_coverage_is_complete(): void
    {
        $faltantes = ParticipantesRequeridosRule::faltantes(
            [['especialidad_id' => 1, 'cantidad_minima' => 2]],
            [
                ['especialidad_id' => 1, 'cantidad' => 1],
                ['especialidad_id' => 1, 'cantidad' => 1],
            ],
        );

        $this->assertSame([], $faltantes);
    }

    public function test_ignores_extra_participants(): void
    {
        $faltantes = ParticipantesRequeridosRule::faltantes(
            [['especialidad_id' => 1, 'cantidad_minima' => 1]],
            [
                ['especialidad_id' => 1, 'cantidad' => 5],
                ['especialidad_id' => 2, 'cantidad' => 1],
            ],
        );

        $this->assertSame([], $faltantes);
    }
}
