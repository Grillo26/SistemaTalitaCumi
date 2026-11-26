<?php

namespace Tests\Unit\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\TipoDiagnosticoCantidadInvalidaException;
use App\Modules\CatalogoServicios\Exceptions\TipoDiagnosticoEspecialidadDuplicadaException;
use App\Modules\CatalogoServicios\Exceptions\TipoDiagnosticoSinEspecialidadesException;
use App\Modules\CatalogoServicios\Rules\TipoDiagnosticoEspecialidadesRule;
use PHPUnit\Framework\TestCase;

final class TipoDiagnosticoEspecialidadesRuleTest extends TestCase
{
    public function test_normalizes_valid_rows(): void
    {
        $this->assertSame([
            ['especialidad_id' => 1, 'cantidad_minima' => 2],
            ['especialidad_id' => 3, 'cantidad_minima' => 1],
        ], TipoDiagnosticoEspecialidadesRule::normalizeAndValidate([
            ['especialidad_id' => '1', 'cantidad_minima' => '2'],
            ['especialidad_id' => 3, 'cantidad_minima' => 1],
        ]));
    }

    public function test_requires_at_least_one_specialty(): void
    {
        $this->expectException(TipoDiagnosticoSinEspecialidadesException::class);
        TipoDiagnosticoEspecialidadesRule::normalizeAndValidate([]);
    }

    public function test_rejects_duplicate_specialties(): void
    {
        $this->expectException(TipoDiagnosticoEspecialidadDuplicadaException::class);
        TipoDiagnosticoEspecialidadesRule::normalizeAndValidate([
            ['especialidad_id' => 1, 'cantidad_minima' => 1],
            ['especialidad_id' => 1, 'cantidad_minima' => 2],
        ]);
    }

    public function test_rejects_quantity_less_than_one(): void
    {
        $this->expectException(TipoDiagnosticoCantidadInvalidaException::class);
        TipoDiagnosticoEspecialidadesRule::normalizeAndValidate([
            ['especialidad_id' => 1, 'cantidad_minima' => 0],
        ]);
    }
}
