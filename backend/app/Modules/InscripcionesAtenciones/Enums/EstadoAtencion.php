<?php

namespace App\Modules\InscripcionesAtenciones\Enums;

/**
 * Estado administrativo de una atención.
 *
 * Solo se escriben `activa` y `retirada`. No hay reactivación ni borrado.
 */
enum EstadoAtencion: string
{
    case ACTIVA = 'activa';
    case RETIRADA = 'retirada';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVA => 'Activa',
            self::RETIRADA => 'Retirada',
        };
    }

    public function permiteEdicion(): bool
    {
        return $this === self::ACTIVA;
    }

    public function permiteBaja(): bool
    {
        return $this === self::ACTIVA;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $estado): string => $estado->value, self::cases());
    }
}
