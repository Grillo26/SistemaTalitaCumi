<?php

namespace App\Modules\InscripcionesAtenciones\Enums;

/**
 * Entidades sobre las que se calcula vigencia.
 */
enum TipoEntidadVigencia: string
{
    case INSCRIPCION_SALA = 'inscripcion_sala';
    case PAQUETE_SESIONES = 'paquete_sesiones';

    public function label(): string
    {
        return match ($this) {
            self::INSCRIPCION_SALA => 'Inscripción a sala',
            self::PAQUETE_SESIONES => 'Paquete de sesiones',
        };
    }

    public function table(): string
    {
        return $this->value;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $tipo): string => $tipo->value, self::cases());
    }
}
