<?php

namespace Tests\Unit\Modules\Seguridad\Rules;

use App\Modules\Seguridad\Rules\UserIdentityNormalizationRule;
use PHPUnit\Framework\TestCase;

final class UserIdentityNormalizationRuleTest extends TestCase
{
    public function test_normalizes_name_email_username_and_documento(): void
    {
        $this->assertSame('María López', UserIdentityNormalizationRule::name('  María   López  '));
        $this->assertSame('maria@example.test', UserIdentityNormalizationRule::email('  MARIA@EXAMPLE.TEST '));
        $this->assertSame('maria.lopez', UserIdentityNormalizationRule::username(' Maria.Lopez '));
        $this->assertSame('1234567-1A', UserIdentityNormalizationRule::documento(' 1234567-1a '));
    }
}
