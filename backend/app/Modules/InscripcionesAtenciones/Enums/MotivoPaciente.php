<?php

namespace App\Modules\InscripcionesAtenciones\Enums;

/**
 * [Estados Paciente e Inscripción] RF-005 / RN-004.
 *
 * Motivos de retiro del paciente. Se reutiliza el código ya existente en
 * bajas y se deja "otro" con detalle obligatorio.
 */
enum MotivoPaciente: string
{
    case RETIRO_PACIENTE = 'retiro_paciente';
    case OTRO = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::RETIRO_PACIENTE => 'Retiro del paciente',
            self::OTRO => 'Otro',
        };
    }

    public function requiereDetalle(): bool
    {
        return $this === self::OTRO;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $motivo): string => $motivo->value, self::cases());
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
