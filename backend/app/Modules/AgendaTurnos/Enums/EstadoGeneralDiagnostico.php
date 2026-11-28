<?php

namespace App\Modules\AgendaTurnos\Enums;

/**
 * Estado general de un diagnóstico (07.05/RF-005, 07.05/RN-004).
 *
 * Coincide con el CHECK real de `atencion_diagnostico.estado_general`.
 */
enum EstadoGeneralDiagnostico: string
{
    case INCOMPLETO = 'incompleto';
    case EN_PROCESO = 'en_proceso';
    case COMPLETO = 'completo';
    case CANCELADO = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::INCOMPLETO => 'Incompleto',
            self::EN_PROCESO => 'En proceso',
            self::COMPLETO => 'Completo',
            self::CANCELADO => 'Cancelado',
        };
    }

    public function esTerminal(): bool
    {
        return $this === self::CANCELADO || $this === self::COMPLETO;
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

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $estado): array => [
                'value' => $estado->value,
                'label' => $estado->label(),
            ],
            self::cases(),
        );
    }
}
