<?php

namespace App\Modules\Pacientes\Support;

use App\Modules\Pacientes\Rules\TutorPhoneRule;

/**
 * Máscara parcial de teléfonos (04.02/RNF-005).
 *
 * En listados se muestra `712***67`; la ficha y el contrato autorizado
 * devuelven el valor completo.
 */
final class PhoneMask
{
    public static function mask(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = TutorPhoneRule::normalize($phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) <= 4) {
            return '****';
        }

        return substr($digits, 0, 3).'***'.substr($digits, -2);
    }
}
