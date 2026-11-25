<?php

namespace App\Modules\Pacientes\Contracts;

use LogicException;

/**
 * Stub de contacto del tutor para la alerta (04.02 Fase C).
 */
final class StubTutorContactContract implements TutorContactContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function contactoPrincipal(int $pacienteId): ?ContactoTutorResumen
    {
        throw new LogicException('04.02 Fase C — Contacto del tutor para alertas no implementado todavía.');
    }
}
