<?php

namespace App\Modules\Pacientes\Enums;

/**
 * Estados del paciente (04.01/RN-004).
 *
 * Solo se admiten tres estados. Las transiciones válidas se inspiran en
 * 06.05/RF-003: activo <-> en_pausa; activo -> retirado; en_pausa -> retirado;
 * retirado -> activo.
 */
enum EstadoPaciente: string
{
    case ACTIVO = 'activo';
    case EN_PAUSA = 'en_pausa';
    case RETIRADO = 'retirado';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVO => 'Activo',
            self::EN_PAUSA => 'En pausa',
            self::RETIRADO => 'Retirado',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $estado): string => $estado->value, self::cases());
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::ACTIVO => [self::EN_PAUSA, self::RETIRADO],
            self::EN_PAUSA => [self::ACTIVO, self::RETIRADO],
            self::RETIRADO => [self::ACTIVO],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function permiteInscripcion(): bool
    {
        return $this === self::ACTIVO;
    }

    public function permiteTurnoNuevo(): bool
    {
        return $this === self::ACTIVO;
    }

    public function isCriticalTransitionFrom(self $from): bool
    {
        return $this === self::RETIRADO || $from === self::RETIRADO;
    }
}
