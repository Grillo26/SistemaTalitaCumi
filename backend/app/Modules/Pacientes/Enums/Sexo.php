<?php

namespace App\Modules\Pacientes\Enums;

/**
 * Sexo del paciente según el esquema (04.01/RF-001).
 *
 * El esquema solo admite M/F. No se inventan catálogos adicionales.
 */
enum Sexo: string
{
    case MASCULINO = 'M';
    case FEMENINO = 'F';

    public function label(): string
    {
        return match ($this) {
            self::MASCULINO => 'Masculino',
            self::FEMENINO => 'Femenino',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $sexo): string => $sexo->value, self::cases());
    }
}
