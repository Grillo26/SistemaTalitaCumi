<?php

namespace App\Modules\Pacientes\Contracts;

/**
 * Vínculo con la entrevista terapéutica del módulo 06.
 *
 * La ficha 04.03 no duplica la entrevista ni su registro clínico.
 */
interface EntrevistaDirectoryContract
{
    public function disponible(): bool;

    public function existe(int $entrevistaId): bool;
}
