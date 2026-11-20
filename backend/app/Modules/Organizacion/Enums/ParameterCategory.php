<?php

namespace App\Modules\Organizacion\Enums;

/**
 * Categorías del catálogo de configuración (RNF-008).
 */
enum ParameterCategory: string
{
    case IDENTIDAD = 'identidad';
    case OPERACION = 'operacion';
    case AGENDA = 'agenda';
    case PAGOS = 'pagos';
    case FORMATO_DOCUMENTOS = 'formato_documentos';
    case ZONA_HORARIA = 'zona_horaria';
    case MONEDA = 'moneda';

    public function label(): string
    {
        return match ($this) {
            self::IDENTIDAD => 'Identidad del centro',
            self::OPERACION => 'Operación',
            self::AGENDA => 'Agenda',
            self::PAGOS => 'Pagos',
            self::FORMATO_DOCUMENTOS => 'Formato de documentos',
            self::ZONA_HORARIA => 'Zona horaria',
            self::MONEDA => 'Moneda',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::IDENTIDAD => 10,
            self::OPERACION => 20,
            self::AGENDA => 30,
            self::PAGOS => 40,
            self::FORMATO_DOCUMENTOS => 50,
            self::ZONA_HORARIA => 60,
            self::MONEDA => 70,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $category): string => $category->value, self::cases());
    }
}
