<?php

namespace App\Modules\InscripcionesAtenciones\Enums;

/**
 * [Estados Paciente e Inscripción] RF-006 / RN-004.
 *
 * Catálogo cerrado inicial de pausa. Los valores provienen de los ejemplos
 * de la especificación; quedan sujetos a validación funcional.
 */
enum MotivoPausa: string
{
    case VIAJE = 'viaje';
    case ENFERMEDAD = 'enfermedad';
    case MOTIVOS_PERSONALES = 'motivos_personales';
    case OTRO = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::VIAJE => 'Viaje familiar',
            self::ENFERMEDAD => 'Enfermedad',
            self::MOTIVOS_PERSONALES => 'Motivos personales',
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
