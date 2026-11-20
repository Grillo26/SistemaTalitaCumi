<?php

namespace App\Modules\Organizacion\Enums;

/**
 * Tipo declarado de un parámetro de configuración (RF-004).
 *
 * El valor se guarda como texto serializado; la conversión se hace con
 * ParameterValueCaster. Nunca se usa float para decimales.
 */
enum ParameterType: string
{
    case ENTERO = 'entero';
    case DECIMAL = 'decimal';
    case HORA = 'hora';
    case TEXTO = 'texto';
    case BOOLEANO = 'booleano';

    public function label(): string
    {
        return match ($this) {
            self::ENTERO => 'número entero',
            self::DECIMAL => 'número decimal',
            self::HORA => 'hora (HH:MM)',
            self::TEXTO => 'texto',
            self::BOOLEANO => 'verdadero o falso',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type): string => $type->value, self::cases());
    }
}
