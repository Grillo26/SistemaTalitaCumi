<?php

namespace App\Modules\AgendaTurnos\Enums;

enum AccionLoteGeneracion: string
{
    case GENERAR = 'generar';
    case REGENERAR = 'regenerar';

    public function label(): string
    {
        return match ($this) {
            self::GENERAR => 'Generación',
            self::REGENERAR => 'Regeneración',
        };
    }
}
