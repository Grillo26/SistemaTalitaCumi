<?php

namespace App\Modules\AgendaTurnos\Enums;

/**
 * Estados de un turno según el schema real de `turno`.
 *
 * RN-006 menciona cuatro estados visibles; el schema también admite
 * `reprogramado`, que se muestra y se cuenta en el resumen.
 */
enum EstadoTurno: string
{
    case PROGRAMADO = 'programado';
    case ASISTIO = 'asistio';
    case FALTO = 'falto';
    case CANCELADO = 'cancelado';
    case REPROGRAMADO = 'reprogramado';

    public function label(): string
    {
        return match ($this) {
            self::PROGRAMADO => 'Programado',
            self::ASISTIO => 'Asistió',
            self::FALTO => 'Faltó',
            self::CANCELADO => 'Cancelado',
            self::REPROGRAMADO => 'Reprogramado',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $estado): string => $estado->value,
            self::cases(),
        );
    }
}
