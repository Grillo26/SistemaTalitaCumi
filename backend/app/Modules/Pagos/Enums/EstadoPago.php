<?php

namespace App\Modules\Pagos\Enums;

/**
 * Estados de un pago. Este submódulo solo crea `vigente`; `anulado` lo
 * escribirá 8.4 (RN-006, RN-011).
 */
enum EstadoPago: string
{
    case VIGENTE = 'vigente';
    case ANULADO = 'anulado';

    public function label(): string
    {
        return match ($this) {
            self::VIGENTE => 'Vigente',
            self::ANULADO => 'Anulado',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $estado): string => $estado->value, self::cases());
    }
}
