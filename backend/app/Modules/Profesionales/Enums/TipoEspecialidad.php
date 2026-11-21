<?php

namespace App\Modules\Profesionales\Enums;

/**
 * Tipo de relación profesional–especialidad (03/RF-E04, 03/RF-E05 / 03/RN-02).
 */
enum TipoEspecialidad: string
{
    case PRINCIPAL = 'principal';
    case SECUNDARIA = 'secundaria';

    public function label(): string
    {
        return match ($this) {
            self::PRINCIPAL => 'Principal',
            self::SECUNDARIA => 'Secundaria',
        };
    }
}
