<?php

namespace Tests\Unit\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Exceptions\PacienteSearchTermTooShortException;
use App\Modules\Pacientes\Rules\PacienteBusquedaRule;
use PHPUnit\Framework\TestCase;

final class PacienteBusquedaRuleTest extends TestCase
{
    public function test_term_is_normalized_without_accents_or_case(): void
    {
        $this->assertSame('maria lopez', PacienteBusquedaRule::term('  MARÍA   López '));
        $this->assertSame('', PacienteBusquedaRule::term(null));
    }

    public function test_tokens_are_split_and_empty_tokens_are_removed(): void
    {
        $this->assertSame(
            ['maria', 'lopez', 'quispe'],
            PacienteBusquedaRule::tokens('maria  lopez quispe'),
        );
    }

    public function test_tutor_phone_is_normalized_to_eight_digits(): void
    {
        $this->assertSame('71234567', PacienteBusquedaRule::tutorPhone('+591 71234567'));
        $this->assertSame('71234567', PacienteBusquedaRule::tutorPhone('59171234567'));
        $this->assertSame('71234567', PacienteBusquedaRule::tutorPhone('071234567'));
        $this->assertNull(PacienteBusquedaRule::tutorPhone('7123'));
        $this->assertNull(PacienteBusquedaRule::tutorPhone('no es teléfono'));
    }

    public function test_sort_whitelist_includes_apellido_and_rejects_unknown_values(): void
    {
        $this->assertTrue(PacienteBusquedaRule::isAllowedSort('apellido'));
        $this->assertTrue(PacienteBusquedaRule::isAllowedSort('nombre_completo'));
        $this->assertTrue(PacienteBusquedaRule::isAllowedSort('fecha_nacimiento'));
        $this->assertTrue(PacienteBusquedaRule::isAllowedSort('estado'));
        $this->assertFalse(PacienteBusquedaRule::isAllowedSort('telefono_tutor'));
        $this->assertFalse(PacienteBusquedaRule::isAllowedSort('random'));
    }

    public function test_search_requires_at_least_two_characters(): void
    {
        PacienteBusquedaRule::assertValid(null);
        PacienteBusquedaRule::assertValid('');
        PacienteBusquedaRule::assertValid('ab');
        $this->addToAssertionCount(3);

        $this->expectException(PacienteSearchTermTooShortException::class);
        PacienteBusquedaRule::assertValid('a');
    }
}
