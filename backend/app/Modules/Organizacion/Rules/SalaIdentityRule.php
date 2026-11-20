<?php

namespace App\Modules\Organizacion\Rules;

/**
 * Identidad de una sala grupal (RN-001 / RF-005).
 *
 * La clave es pura; la comprobación contra la BD la hace la Action/Guard.
 */
final class SalaIdentityRule
{
    public static function key(int $sucursalId, int $tipoSalaId, int $turnoCatalogoId): string
    {
        return "{$sucursalId}:{$tipoSalaId}:{$turnoCatalogoId}";
    }

    /**
     * @param  list<string>  $existingKeys
     */
    public static function isUnique(string $candidateKey, array $existingKeys): bool
    {
        return ! in_array($candidateKey, $existingKeys, true);
    }
}
