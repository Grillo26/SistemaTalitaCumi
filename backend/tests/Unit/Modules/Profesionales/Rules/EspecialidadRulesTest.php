<?php

namespace Tests\Unit\Modules\Profesionales\Rules;

use App\Modules\Profesionales\Exceptions\DuplicateEspecialidadNameException;
use App\Modules\Profesionales\Exceptions\EspecialidadEnUsoException;
use App\Modules\Profesionales\Rules\EspecialidadDeactivationRule;
use App\Modules\Profesionales\Rules\EspecialidadIdentityRule;
use App\Modules\Profesionales\Rules\EspecialidadNameUniquenessRule;
use PHPUnit\Framework\TestCase;

final class EspecialidadRulesTest extends TestCase
{
    public function test_name_normalization(): void
    {
        $this->assertSame('Terapia Ocupacional', EspecialidadIdentityRule::normalizeName('  Terapia   Ocupacional '));
    }

    public function test_name_uniqueness_rule(): void
    {
        $rule = new EspecialidadNameUniquenessRule;
        $rule->assertAvailable(null);

        $this->expectException(DuplicateEspecialidadNameException::class);
        $rule->assertAvailable(3);
    }

    public function test_deactivation_rule_blocks_active_professionals(): void
    {
        $rule = new EspecialidadDeactivationRule;
        $rule->assertCanDeactivate(0, 'Psicología');

        $this->expectException(EspecialidadEnUsoException::class);
        $rule->assertCanDeactivate(2, 'Psicología');
    }
}
