<?php

namespace App\Modules\Pacientes\Enums;

/**
 * Tipo de coincidencia de duplicado (04.01/RF-003, 04.01/RF-016).
 */
enum TipoCoincidencia: string
{
    case IDENTIDAD_COMPLETA = 'identidad_completa';
    case IDENTIDAD_PARCIAL = 'identidad_parcial';
    case IDENTIDAD_PARCIAL_CON_TUTOR = 'identidad_parcial_con_tutor';

    public function label(): string
    {
        return match ($this) {
            self::IDENTIDAD_COMPLETA => 'Mismos nombres y fecha de nacimiento',
            self::IDENTIDAD_PARCIAL => 'Coincidencia parcial de nombre y fecha',
            self::IDENTIDAD_PARCIAL_CON_TUTOR => 'Coincidencia parcial con el mismo tutor',
        };
    }
}
