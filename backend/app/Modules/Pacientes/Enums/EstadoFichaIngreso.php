<?php

namespace App\Modules\Pacientes\Enums;

/**
 * Estado de la ficha de ingreso (04.03/RN-005, 04.03/RNF-007 bis).
 */
enum EstadoFichaIngreso: string
{
    case BORRADOR = 'borrador';

    case COMPLETA = 'completa';

    case ARCHIVADA = 'archivada';

    public function label(): string
    {
        return match ($this) {
            self::BORRADOR => 'Borrador',
            self::COMPLETA => 'Completa',
            self::ARCHIVADA => 'Archivada',
        };
    }

    public function estaArchivada(): bool
    {
        return $this === self::ARCHIVADA;
    }
}
