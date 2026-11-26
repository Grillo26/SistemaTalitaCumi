<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\NotasAdministrativasInvalidasException;

/**
 * RF-015: notas administrativas de hasta 500 caracteres, sin datos clínicos.
 */
final class NotasAdministrativasRule
{
    public static function assertValidas(?string $notas): void
    {
        if ($notas !== null && mb_strlen(trim($notas)) > 500) {
            throw new NotasAdministrativasInvalidasException;
        }
    }
}
