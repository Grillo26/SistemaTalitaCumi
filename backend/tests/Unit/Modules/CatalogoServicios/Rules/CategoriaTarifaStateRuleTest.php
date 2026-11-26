<?php

namespace Tests\Unit\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\CategoriaTarifaConTarifasVigentesException;
use App\Modules\CatalogoServicios\Rules\CategoriaTarifaStateRule;
use PHPUnit\Framework\TestCase;

final class CategoriaTarifaStateRuleTest extends TestCase
{
    public function test_category_with_active_tariffs_cannot_be_deactivated(): void
    {
        $this->expectException(CategoriaTarifaConTarifasVigentesException::class);
        CategoriaTarifaStateRule::assertCanDeactivate(true, 'General', 2);
    }

    public function test_category_without_active_tariffs_can_be_deactivated(): void
    {
        CategoriaTarifaStateRule::assertCanDeactivate(false, 'General', 0);

        $this->assertTrue(true);
    }
}
