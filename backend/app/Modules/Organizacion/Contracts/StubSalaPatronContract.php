<?php

namespace App\Modules\Organizacion\Contracts;

use LogicException;

/**
 * Stub para RF-001 mientras Salas no exponga el patrón de días y hora.
 *
 * Falla de forma explícita: nunca inventa días, horas ni profesional.
 */
final class StubSalaPatronContract implements SalaPatronContract
{
    public function patron(int $salaId): ?SalaPatronResumen
    {
        throw new LogicException(
            'El patrón de días y hora de las salas todavía no está implementado (Organización/Salas).',
        );
    }
}
