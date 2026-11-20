<?php

namespace Tests\Unit\Modules\Organizacion\Rules;

use App\Modules\Organizacion\Exceptions\BranchDeletionNotAllowedException;
use App\Modules\Organizacion\Exceptions\BranchStatusReasonRequiredException;
use App\Modules\Organizacion\Exceptions\BranchStatusUnchangedException;
use App\Modules\Organizacion\Exceptions\InactiveBranchException;
use App\Modules\Organizacion\Rules\BranchDeletionRule;
use App\Modules\Organizacion\Rules\BranchIdentityNormalizationRule;
use App\Modules\Organizacion\Rules\BranchInitialStatusRule;
use App\Modules\Organizacion\Rules\BranchOperationalContextRule;
use App\Modules\Organizacion\Rules\BranchOperationalStatusRule;
use App\Modules\Organizacion\Rules\BranchStatusChangeRule;
use PHPUnit\Framework\TestCase;

final class BranchRulesTest extends TestCase
{
    public function test_normalizes_code_name_and_comparison_value(): void
    {
        $this->assertSame('SUC-001', BranchIdentityNormalizationRule::normalizeCode('  suc-001  '));
        $this->assertSame('Sucursal Central', BranchIdentityNormalizationRule::normalizeName('  Sucursal   Central  '));
        $this->assertSame('sucursal central', BranchIdentityNormalizationRule::fold('  Sucursal   Central  '));
    }

    public function test_initial_status_defaults_to_active(): void
    {
        $this->assertTrue(BranchInitialStatusRule::resolve(null));
        $this->assertTrue(BranchInitialStatusRule::resolve(true));
        $this->assertFalse(BranchInitialStatusRule::resolve(false));
    }

    public function test_status_reason_is_required(): void
    {
        $this->expectException(BranchStatusReasonRequiredException::class);

        (new BranchStatusChangeRule)->reason('   ');
    }

    public function test_status_reason_is_trimmed(): void
    {
        $this->assertSame('Cierre temporal', (new BranchStatusChangeRule)->reason('  Cierre temporal  '));
    }

    public function test_status_must_actually_change(): void
    {
        $this->expectException(BranchStatusUnchangedException::class);

        (new BranchStatusChangeRule)->assertDifferent(true, true);
    }

    public function test_operational_status_rejects_inactive_branch(): void
    {
        $this->expectException(InactiveBranchException::class);

        BranchOperationalStatusRule::assertActive(false, 1, 'Central');
    }

    public function test_operational_status_accepts_active_branch(): void
    {
        BranchOperationalStatusRule::assertActive(true, 1, 'Central');

        $this->assertTrue(true);
    }

    public function test_deletion_rule_rejects_physical_delete(): void
    {
        $this->expectException(BranchDeletionNotAllowedException::class);

        BranchDeletionRule::reject('Central');
    }

    public function test_operational_context_is_unique(): void
    {
        $this->assertTrue(BranchOperationalContextRule::matches('BOB', 'America/La_Paz'));
        $this->assertFalse(BranchOperationalContextRule::matches('USD', 'America/La_Paz'));
        $this->assertFalse(BranchOperationalContextRule::matches('BOB', 'UTC'));
    }
}
