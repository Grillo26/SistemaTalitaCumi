<?php

namespace App\Modules\InscripcionesAtenciones\Enums;

/**
 * Días de la semana admitidos por el patrón del paquete (RN-002).
 */
enum DiaSemana: int
{
    case LUNES = 1;
    case MARTES = 2;
    case MIERCOLES = 3;
    case JUEVES = 4;
    case VIERNES = 5;
    case SABADO = 6;

    public function label(): string
    {
        return match ($this) {
            self::LUNES => 'Lunes',
            self::MARTES => 'Martes',
            self::MIERCOLES => 'Miércoles',
            self::JUEVES => 'Jueves',
            self::VIERNES => 'Viernes',
            self::SABADO => 'Sábado',
        };
    }

    /**
     * @return list<int>
     */
    public static function values(): array
    {
        return array_map(static fn (self $dia): int => $dia->value, self::cases());
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $dia): array => [
                'value' => $dia->value,
                'label' => $dia->label(),
            ],
            self::cases(),
        );
    }

    public static function labelFor(int $value): string
    {
        return self::tryFrom($value)?->label() ?? (string) $value;
    }
}
