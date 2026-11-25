<?php

namespace Tests\Unit\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Exceptions\TutorPrincipalReasonRequiredException;
use App\Modules\Pacientes\Exceptions\TutorStatusReasonRequiredException;
use App\Modules\Pacientes\Exceptions\TutorStatusUnchangedException;
use App\Modules\Pacientes\Rules\TutorIdentityEditScopeRule;
use App\Modules\Pacientes\Rules\TutorPrincipalChangeRule;
use App\Modules\Pacientes\Rules\TutorStatusChangeRule;
use PHPUnit\Framework\TestCase;

final class TutorRulesTest extends TestCase
{
    public function test_status_reason_is_required_only_when_deactivating(): void
    {
        $rule = new TutorStatusChangeRule;

        $this->assertNull($rule->reason(true, null));
        $this->assertSame('Vacaciones', $rule->reason(false, ' Vacaciones '));

        $this->expectException(TutorStatusReasonRequiredException::class);
        $rule->reason(false, '   ');
    }

    public function test_status_cannot_be_changed_to_the_same_value(): void
    {
        $rule = new TutorStatusChangeRule;

        $rule->assertDifferent(false, true);
        $this->addToAssertionCount(1);

        $this->expectException(TutorStatusUnchangedException::class);
        $rule->assertDifferent(true, true);
    }

    public function test_principal_change_reason_is_required_only_when_replacing(): void
    {
        $rule = new TutorPrincipalChangeRule;

        $this->assertNull($rule->reason(false, null));
        $this->assertSame('Cambio solicitado', $rule->reason(true, 'Cambio solicitado'));

        $this->expectException(TutorPrincipalReasonRequiredException::class);
        $rule->reason(true, '');
    }

    public function test_identity_edit_requires_all_tutor_branches_in_scope(): void
    {
        $rule = new TutorIdentityEditScopeRule;

        $this->assertTrue($rule->canEditIdentity(null, [1, 2]));
        $this->assertTrue($rule->canEditIdentity([1, 2, 3], [1, 2]));
        $this->assertFalse($rule->canEditIdentity([1], [1, 2]));
        $this->assertFalse($rule->canEditIdentity([], [1]));
        $this->assertFalse($rule->canEditIdentity([1], []));
    }
}
