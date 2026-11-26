<?php

namespace Tests\Unit\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\ServicioNoEsDiagnosticoException;
use App\Modules\CatalogoServicios\Exceptions\TipoDiagnosticoDiasInvalidosException;
use App\Modules\CatalogoServicios\Exceptions\TipoDiagnosticoNombreDuplicadoException;
use App\Modules\CatalogoServicios\Rules\NombreNormalizadoRule;
use App\Modules\CatalogoServicios\Rules\TipoDiagnosticoDiasRule;
use App\Modules\CatalogoServicios\Rules\TipoDiagnosticoNombreUnicoRule;
use App\Modules\CatalogoServicios\Rules\TipoDiagnosticoServicioRule;
use PHPUnit\Framework\TestCase;

final class TipoDiagnosticoRulesTest extends TestCase
{
    public function test_name_uniqueness_is_case_and_space_insensitive(): void
    {
        $this->expectException(TipoDiagnosticoNombreDuplicadoException::class);
        TipoDiagnosticoNombreUnicoRule::assertAvailable(
            NombreNormalizadoRule::fold('evaluación integral'),
            [NombreNormalizadoRule::fold('  Evaluación   integral ')],
        );
    }

    public function test_service_must_be_diagnostic_type(): void
    {
        $this->expectException(ServicioNoEsDiagnosticoException::class);
        TipoDiagnosticoServicioRule::assertEsDiagnostico('entrevista', 'Entrevista');
    }

    public function test_diagnostic_service_is_accepted(): void
    {
        TipoDiagnosticoServicioRule::assertEsDiagnostico(
            TipoDiagnosticoServicioRule::CODIGO_DIAGNOSTICO,
            'Evaluación',
        );

        $this->assertTrue(true);
    }

    public function test_days_must_be_positive(): void
    {
        $this->assertSame(2, TipoDiagnosticoDiasRule::normalizeAndValidate('2'));

        $this->expectException(TipoDiagnosticoDiasInvalidosException::class);
        TipoDiagnosticoDiasRule::normalizeAndValidate(0);
    }
}
