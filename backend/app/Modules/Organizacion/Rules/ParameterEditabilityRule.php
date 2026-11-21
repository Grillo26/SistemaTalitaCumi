<?php

namespace App\Modules\Organizacion\Rules;

use App\Modules\Organizacion\Exceptions\ParameterNotEditableException;

/**
 * Los parámetros de solo lectura no se editan desde la pantalla (RN-003 / RN-010).
 */
final class ParameterEditabilityRule
{
    public static function assertEditable(bool $editable, string $clave): void
    {
        if (! $editable) {
            throw new ParameterNotEditableException($clave);
        }
    }
}
