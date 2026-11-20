<?php

namespace App\Modules\Organizacion\Rules;

use App\Modules\Organizacion\Exceptions\TipoSalaInUseException;

/**
 * Un tipo de sala no se desactiva si tiene salas activas (RF-002).
 */
final class TipoSalaDeactivationRule
{
    public static function assertCanDeactivate(bool $hasActiveSalas, string $tipoSalaName, int $activeSalasCount): void
    {
        if ($hasActiveSalas) {
            throw new TipoSalaInUseException($tipoSalaName, $activeSalasCount);
        }
    }
}
