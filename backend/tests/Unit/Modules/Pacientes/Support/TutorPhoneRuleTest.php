<?php

namespace Tests\Unit\Modules\Pacientes\Support;

use App\Modules\Pacientes\Rules\TutorPhoneRule;
use App\Modules\Pacientes\Support\PhoneMask;
use PHPUnit\Framework\TestCase;

final class TutorPhoneRuleTest extends TestCase
{
    public function test_normalizes_country_prefix_and_leading_zero(): void
    {
        $this->assertSame('71234567', TutorPhoneRule::normalize('+591 712-345-67'));
        $this->assertSame('71234567', TutorPhoneRule::normalize('59171234567'));
        $this->assertSame('71234567', TutorPhoneRule::normalize('071234567'));
        $this->assertSame('71234567', TutorPhoneRule::normalize(' 712 345 67 '));
        $this->assertNull(TutorPhoneRule::normalize(null));
        $this->assertNull(TutorPhoneRule::normalize('   '));
    }

    public function test_principal_phone_only_accepts_mobile_prefixes(): void
    {
        $this->assertTrue(TutorPhoneRule::isValidPrincipal('71234567'));
        $this->assertTrue(TutorPhoneRule::isValidPrincipal('61234567'));
        $this->assertFalse(TutorPhoneRule::isValidPrincipal('21234567'));
        $this->assertFalse(TutorPhoneRule::isValidPrincipal('7123456'));
        $this->assertFalse(TutorPhoneRule::isValidPrincipal('712345678'));
    }

    public function test_alternate_phone_accepts_fixed_and_mobile_prefixes(): void
    {
        $this->assertTrue(TutorPhoneRule::isValidAlternate('21234567'));
        $this->assertTrue(TutorPhoneRule::isValidAlternate('31234567'));
        $this->assertTrue(TutorPhoneRule::isValidAlternate('41234567'));
        $this->assertTrue(TutorPhoneRule::isValidAlternate('61234567'));
        $this->assertTrue(TutorPhoneRule::isValidAlternate('71234567'));
        $this->assertFalse(TutorPhoneRule::isValidAlternate('11234567'));
        $this->assertFalse(TutorPhoneRule::isValidAlternate('51234567'));
    }

    public function test_mask_hides_the_middle_digits(): void
    {
        $this->assertSame('712***67', PhoneMask::mask('71234567'));
        $this->assertSame('712***67', PhoneMask::mask('+591 712-345-67'));
        $this->assertSame('****', PhoneMask::mask('123'));
        $this->assertNull(PhoneMask::mask(null));
        $this->assertNull(PhoneMask::mask('   '));
    }
}
