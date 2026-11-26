<?php

namespace Tests\Unit\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\AreaConServiciosActivosException;
use App\Modules\CatalogoServicios\Exceptions\AreaNombreDuplicadoException;
use App\Modules\CatalogoServicios\Exceptions\MotivoRequeridoException;
use App\Modules\CatalogoServicios\Exceptions\ServicioNombreDuplicadoException;
use App\Modules\CatalogoServicios\Rules\AreaNombreUnicoRule;
use App\Modules\CatalogoServicios\Rules\AreaStateRule;
use App\Modules\CatalogoServicios\Rules\MotivoObligatorioRule;
use App\Modules\CatalogoServicios\Rules\NombreNormalizadoRule;
use App\Modules\CatalogoServicios\Rules\ServicioNombreUnicoRule;
use PHPUnit\Framework\TestCase;

final class CatalogoRulesTest extends TestCase
{
    public function test_name_normalization_and_fold(): void
    {
        $this->assertSame('Sala Maternal', NombreNormalizadoRule::normalize('  Sala   Maternal  '));
        $this->assertSame('sala maternal', NombreNormalizadoRule::fold('  Sala   Maternal  '));
    }

    public function test_area_name_uniqueness_is_case_and_space_insensitive(): void
    {
        $existing = [NombreNormalizadoRule::fold('  Salas   Grupales ')];

        $this->expectException(AreaNombreDuplicadoException::class);
        AreaNombreUnicoRule::assertAvailable(
            NombreNormalizadoRule::fold('salas grupales'),
            $existing,
        );
    }

    public function test_service_name_uniqueness_within_area(): void
    {
        $existing = [NombreNormalizadoRule::fold('Terapia de lenguaje')];

        $this->expectException(ServicioNombreDuplicadoException::class);
        ServicioNombreUnicoRule::assertAvailable(
            NombreNormalizadoRule::fold(' terapia   de lenguaje '),
            $existing,
        );
    }

    public function test_area_with_active_services_cannot_be_deactivated(): void
    {
        $this->expectException(AreaConServiciosActivosException::class);
        AreaStateRule::assertCanDeactivate(true, 'Intervención Terapéutica', 2);
    }

    public function test_area_without_active_services_can_be_deactivated(): void
    {
        AreaStateRule::assertCanDeactivate(false, 'Intervención Terapéutica', 0);
        $this->assertTrue(true);
    }

    public function test_reason_is_required_and_trimmed(): void
    {
        $this->expectException(MotivoRequeridoException::class);
        MotivoObligatorioRule::require('   ');
    }

    public function test_reason_is_trimmed(): void
    {
        $this->assertSame('Cierre temporal', MotivoObligatorioRule::require('  Cierre temporal  '));
    }
}
