<?php

namespace Tests\Unit\Modules\Seguridad\Support;

use App\Modules\Seguridad\Support\PasswordPolicy;
use App\Modules\Seguridad\Support\PasswordPolicyConfig;
use App\Modules\Seguridad\Support\TemporaryPasswordGenerator;
use PHPUnit\Framework\TestCase;

final class TemporaryPasswordGeneratorTest extends TestCase
{
    public function test_generated_password_satisfies_default_policy(): void
    {
        $policy = new PasswordPolicy(new PasswordPolicyConfig(
            minLength: 10,
            requireUppercase: true,
            requireLowercase: true,
            requireNumber: true,
            requireSymbol: true,
            historyCount: 5,
            expiryDays: 0,
        ));

        $generator = new TemporaryPasswordGenerator;

        for ($i = 0; $i < 10; $i++) {
            $password = $generator->generate();

            $this->assertSame(16, strlen($password));
            $this->assertSame([], $policy->validate($password));
        }
    }
}
