<?php

namespace App\Modules\AgendaTurnos\Enums;

enum TipoOrigenTurno: string
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

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $tipo): string => $tipo->value, self::cases());
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $tipo): array => [
                'value' => $tipo->value,
                'label' => $tipo->label(),
            ],
            self::cases(),
        );
    }
}
