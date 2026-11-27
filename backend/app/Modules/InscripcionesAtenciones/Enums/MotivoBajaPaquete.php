<?php

namespace App\Modules\InscripcionesAtenciones\Enums;

/**
 * Motivos cerrados de baja de un paquete (RF-014).
 */
enum MotivoBajaPaquete: string
{
    case RETIRO_PACIENTE = 'retiro_paciente';
    case CAMBIO_SERVICIO = 'cambio_servicio';
    case CAMBIO_PROFESIONAL = 'cambio_profesional';
    case OTRO = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::RETIRO_PACIENTE => 'Retiro del paciente',
            self::CAMBIO_SERVICIO => 'Cambio de servicio',
            self::CAMBIO_PROFESIONAL => 'Cambio de profesional',
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
