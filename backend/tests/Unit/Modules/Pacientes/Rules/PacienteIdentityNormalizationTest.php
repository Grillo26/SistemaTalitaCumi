<?php

namespace Tests\Unit\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Rules\PacienteIdentityRule;
use App\Modules\Pacientes\Support\PacienteNormalizer;
use PHPUnit\Framework\TestCase;

final class PacienteIdentityNormalizationTest extends TestCase
{
    public function test_normalizes_accents_case_and_spaces(): void
    {
        $this->assertSame('jose maria', PacienteNormalizer::key('  José   María  '));
        $this->assertSame('nandu', PacienteNormalizer::key('Ñandú'));
        $this->assertSame('lopez quispe', PacienteNormalizer::key('LÓPEZ  Quispe'));
    }

    public function test_document_key_is_lowercase_and_trimmed(): void
    {
        $this->assertSame('abc-1', PacienteNormalizer::documentKey('  ABC-1 '));
        $this->assertNull(PacienteNormalizer::documentKey('   '));
    }

    public function test_display_document_is_uppercase(): void
    {
        $this->assertSame('ABC-1', PacienteIdentityRule::normalizeDocument(' abc-1 '));
        $this->assertNull(PacienteIdentityRule::normalizeDocument('  '));
    }

    public function test_phone_key_keeps_digits_only(): void
    {
        $this->assertSame('59171234567', PacienteNormalizer::phoneKey('+591 712-345-67'));
    }
}
