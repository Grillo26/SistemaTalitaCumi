<?php

namespace App\Modules\AgendaTurnos\Enums;

enum SeveridadHallazgo: string
{
    case BLOQUEO = 'bloqueo';
    case ADVERTENCIA = 'advertencia';
    case INFORMATIVO = 'informativo';

    public function label(): string
    {
        return match ($this) {
            self::BLOQUEO => 'Bloqueo',
            self::ADVERTENCIA => 'Advertencia',
            self::INFORMATIVO => 'Informativo',
        };
    }
}
