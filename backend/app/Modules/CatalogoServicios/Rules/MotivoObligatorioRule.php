<?php

namespace App\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\MotivoRequeridoException;

/**
 * Motivo obligatorio en cambios de estado (RN-C10 / RF-C12).
 */
final class MotivoObligatorioRule
{
    public static function require(?string $motivo): string
    {
        $normalized = trim((string) $motivo);

        if ($normalized === '') {
            throw new MotivoRequeridoException;
        }

        return $normalized;
    }
}
