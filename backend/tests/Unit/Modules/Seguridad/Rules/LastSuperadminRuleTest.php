<?php

namespace Tests\Unit\Modules\Seguridad\Rules;

use App\Modules\Seguridad\Exceptions\LastSuperadminProtectedException;
use App\Modules\Seguridad\Rules\LastSuperadminRule;
use PHPUnit\Framework\TestCase;

final class LastSuperadminRuleTest extends TestCase
{
    public function test_last_active_superadmin_cannot_be_deactivated(): void
    {
        $this->expectException(LastSuperadminProtectedException::class);

        (new LastSuperadminRule)->assertCanDeactivate(true, true, 1);
    }

    public function test_last_active_superadmin_cannot_lose_role(): void
    {
        $this->expectException(LastSuperadminProtectedException::class);

        (new LastSuperadminRule)->assertCanChangeRole(true, true, false, 1);
    }

    public function test_second_superadmin_can_be_deactivated(): void
    {
        (new LastSuperadminRule)->assertCanDeactivate(true, true, 2);

        $this->assertTrue(true);
    }
}
