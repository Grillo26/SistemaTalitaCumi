<?php

namespace App\Modules\Pacientes\Contracts;

use LogicException;

/**
 * Stub del vínculo con 06 mientras Entrevistas no exista.
 */
final class StubEntrevistaDirectoryContract implements EntrevistaDirectoryContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function existe(int $entrevistaId): bool
    {
        throw new LogicException('Módulo 06 — Entrevista y diagnóstico no implementado todavía.');
    }
}
