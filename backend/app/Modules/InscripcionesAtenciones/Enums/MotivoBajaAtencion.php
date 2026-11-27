<?php

namespace App\Modules\InscripcionesAtenciones\Enums;

/**
 * Motivos cerrados de baja de una atención.
 */
enum MotivoBajaAtencion: string
{
    case RETIRO_PACIENTE = 'retiro_paciente';
    case ERROR_REGISTRO = 'error_registro';
    case OTRO = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::RETIRO_PACIENTE => 'Retiro del paciente',
            self::ERROR_REGISTRO => 'Error de registro',
            self::OTRO => 'Otro',
        };
    }

    public function requiereDetalle(): bool
    {
        return $this === self::OTRO;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $motivo): array => [
                'value' => $motivo->value,
                'label' => $motivo->label(),
            ],
            self::cases(),
        );
    }
}
