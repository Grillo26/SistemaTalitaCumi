<?php

namespace App\Modules\AgendaTurnos\Enums;

/**
 * Acciones registradas en el historial/bitácora de un turno.
 *
 * ASISTIO y FALTO las escribirá 7.4; se incluyen aquí para que el historial
 * tenga un contrato estable y no haya que migrar el catálogo después.
 */
enum AccionCambioTurno: string
{
    case REPROGRAMAR = 'REPROGRAMAR';
    case CANCELAR = 'CANCELAR';
    case CANCELAR_MASIVO = 'CANCELAR_MASIVO';
    case CREAR_POR_REPROGRAMACION = 'CREAR_POR_REPROGRAMACION';
    case ASISTIO = 'ASISTIO';
    case FALTO = 'FALTO';

    public function label(): string
    {
        return match ($this) {
            self::REPROGRAMAR => 'Reprogramación',
            self::CANCELAR => 'Cancelación',
            self::CANCELAR_MASIVO => 'Cancelación masiva',
            self::CREAR_POR_REPROGRAMACION => 'Creado por reprogramación',
            self::ASISTIO => 'Asistió',
            self::FALTO => 'Faltó',
        };
    }

    public function esCancelacion(): bool
    {
        return $this === self::CANCELAR || $this === self::CANCELAR_MASIVO;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $accion): string => $accion->value, self::cases());
    }
}
