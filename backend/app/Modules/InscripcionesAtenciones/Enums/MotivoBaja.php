<?php

namespace App\Modules\InscripcionesAtenciones\Enums;

/**
 * Motivos cerrados de baja de una inscripción a sala grupal (RF-011).
 *
 * El schema no modela una tabla de motivos. Se usa este catálogo fijo y el
 * texto libre se guarda junto con el evento de bitácora.
 */
enum MotivoBaja: string
{
    case RETIRO_PACIENTE = 'retiro_paciente';
    case CAMBIO_SALA = 'cambio_sala';
    case CAMBIO_SUCURSAL = 'cambio_sucursal';
    case OTRO = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::RETIRO_PACIENTE => 'Retiro del paciente',
            self::CAMBIO_SALA => 'Cambio de sala',
            self::CAMBIO_SUCURSAL => 'Cambio de sucursal',
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
