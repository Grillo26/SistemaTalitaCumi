<?php

namespace App\Modules\Profesionales\Support;

/**
 * Claves y TTL del validador cacheado (03/RNF validación < 100 ms).
 */
final class ProfesionalValidatorCacheKeys
{
    public const EPOCH_KEY = 'profesionales.validador.epoch';

    public const TTL_SECONDS = 300;

    public static function epoch(): int
    {
        return (int) cache()->get(self::EPOCH_KEY, 0);
    }

    public static function forTriple(
        int $epoch,
        int $profesionalId,
        int $especialidadId,
        int $sucursalId,
    ): string {
        return sprintf(
            'profesionales.validador.%d.%d.%d.%d',
            $epoch,
            $profesionalId,
            $especialidadId,
            $sucursalId,
        );
    }
}
