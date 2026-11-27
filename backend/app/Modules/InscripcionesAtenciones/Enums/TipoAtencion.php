<?php

namespace App\Modules\InscripcionesAtenciones\Enums;

/**
 * Tipo de atención del submódulo 03.
 */
enum TipoAtencion: string
{
    case ENTREVISTA = 'entrevista';
    case DIAGNOSTICO = 'diagnostico';

    public function label(): string
    {
        return match ($this) {
            self::ENTREVISTA => 'Entrevista',
            self::DIAGNOSTICO => 'Diagnóstico',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $tipo): string => $tipo->value, self::cases());
    }
}
