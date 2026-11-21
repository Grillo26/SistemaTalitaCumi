<?php

namespace Tests\Unit\Modules\Seguridad\Rules;

use App\Modules\Seguridad\Support\PasswordPolicy;
use App\Modules\Seguridad\Support\PasswordPolicyConfig;
use PHPUnit\Framework\TestCase;

final class PasswordPolicyTest extends TestCase
{
    private function policy(bool $symbol = false): PasswordPolicy
    {
        return new PasswordPolicy(new PasswordPolicyConfig(
            minLength: 10,
            requireUppercase: true,
            requireLowercase: true,
            requireNumber: true,
            requireSymbol: $symbol,
            historyCount: 5,
            expiryDays: 0,
        ));
    }

    public function test_valid_password_passes(): void
    {
        $this->assertSame([], $this->policy()->validate('Secreta123!'));
    }

    public function test_minimum_length_is_enforced(): void
    {
        $errors = $this->policy()->validate('Ab1!');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('10', $errors[0]);
    }

    public function test_uppercase_is_required(): void
    {
        $errors = $this->policy()->validate('secreta123!');

        $this->assertTrue($this->contains($errors, 'mayúscula'));
    }

    public function test_lowercase_is_required(): void
    {
        $errors = $this->policy()->validate('SECRETA123!');

        $this->assertTrue($this->contains($errors, 'minúscula'));
    }

    public function test_number_is_required(): void
    {
        $errors = $this->policy()->validate('SecretaClave!');

        $this->assertTrue($this->contains($errors, 'número'));
    }

    public function test_symbol_can_be_required(): void
    {
        $errors = $this->policy(true)->validate('Secreta123');

        $this->assertTrue($this->contains($errors, 'símbolo'));
    }

    public function test_bcrypt_byte_limit_is_enforced(): void
    {
        $errors = $this->policy()->validate(str_repeat('A', 70).'1a!');

        $this->assertTrue($this->contains($errors, '72'));
    }

    /**
     * @param  list<string>  $errors
     */
    private function contains(array $errors, string $needle): bool
    {
        foreach ($errors as $error) {
            if (str_contains($error, $needle)) {
                return true;
            }
        }

        return false;
    }
}
