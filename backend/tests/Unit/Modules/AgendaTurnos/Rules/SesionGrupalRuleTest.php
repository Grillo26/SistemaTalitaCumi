<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Rules\SesionGrupalRule;
use PHPUnit\Framework\TestCase;

/**
 * [Programación de Turnos] RF-005 / RF-006 / CA-004.
 */
final class SesionGrupalRuleTest extends TestCase
{
    public function test_builds_the_same_key_for_the_same_group_session(): void
    {
        $a = SesionGrupalRule::clave(5, '2025-12-02', '09:00', '09:50', 10);
        $b = SesionGrupalRule::clave(5, '2025-12-02', '09:00', '09:50', 10);

        $this->assertNotNull($a);
        $this->assertTrue(SesionGrupalRule::misma($a, (string) $b));
    }

    public function test_different_service_is_another_session(): void
    {
        $a = SesionGrupalRule::clave(5, '2025-12-02', '09:00', '09:50', 10);
        $b = SesionGrupalRule::clave(5, '2025-12-02', '09:00', '09:50', 11);

        $this->assertFalse(SesionGrupalRule::misma((string) $a, (string) $b));
    }

    public function test_without_room_there_is_no_group_session_key(): void
    {
        $this->assertNull(SesionGrupalRule::clave(null, '2025-12-02', '09:00', '09:50', 10));
    }
}
