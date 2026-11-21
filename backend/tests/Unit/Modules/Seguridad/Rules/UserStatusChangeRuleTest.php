<?php

namespace Tests\Unit\Modules\Seguridad\Rules;

use App\Modules\Seguridad\Exceptions\UserStatusReasonRequiredException;
use App\Modules\Seguridad\Exceptions\UserStatusUnchangedException;
use App\Modules\Seguridad\Rules\UserStatusChangeRule;
use PHPUnit\Framework\TestCase;

final class UserStatusChangeRuleTest extends TestCase
{
    public function test_deactivation_requires_reason(): void
    {
        $this->expectException(UserStatusReasonRequiredException::class);

        (new UserStatusChangeRule)->reason('   ', false);
    }

    public function test_activation_allows_empty_reason(): void
    {
        $this->assertNull((new UserStatusChangeRule)->reason('', true));
    }

    public function test_unchanged_status_is_rejected(): void
    {
        $this->expectException(UserStatusUnchangedException::class);

        (new UserStatusChangeRule)->assertDifferent(true, true);
    }
}
