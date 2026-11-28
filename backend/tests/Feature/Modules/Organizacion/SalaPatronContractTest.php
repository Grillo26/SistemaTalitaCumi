<?php

namespace Tests\Feature\Modules\Organizacion;

use App\Modules\Organizacion\Contracts\SalaPatronContract;
use App\Modules\Organizacion\Contracts\StubSalaPatronContract;
use LogicException;

/**
 * [Programación de Turnos] RF-001 pendiente: Salas aún no expone el patrón.
 */
final class SalaPatronContractTest extends OrganizacionTestCase
{
    public function test_the_binding_is_the_explicit_stub(): void
    {
        $this->assertInstanceOf(
            StubSalaPatronContract::class,
            app(SalaPatronContract::class),
        );
    }

    public function test_the_stub_fails_loudly_instead_of_inventing_a_pattern(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('patrón de días y hora');

        app(SalaPatronContract::class)->patron(1);
    }
}
