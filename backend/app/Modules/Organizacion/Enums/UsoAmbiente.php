<?php

namespace App\Modules\Organizacion\Enums;

/**
 * Uso previsto de un ambiente (RF-006).
 */
enum UsoAmbiente: string
{
    case TerapiaIndividual = 'terapia_individual';
    case Entrevista = 'entrevista';
    case Diagnostico = 'diagnostico';
    case Mixto = 'mixto';

    public function label(): string
    {
        return match ($this) {
            self::TerapiaIndividual => 'Terapia individual',
            self::Entrevista => 'Entrevista',
            self::Diagnostico => 'Diagnóstico',
            self::Mixto => 'Mixto',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $uso): string => $uso->value, self::cases());
    }
}
