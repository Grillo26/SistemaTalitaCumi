<?php

namespace App\Modules\Organizacion\Rules;

use App\Modules\Organizacion\Exceptions\InactiveCatalogException;

/**
 * Un catálogo inactivo no puede usarse en una sala nueva (RN-002).
 */
final class CatalogAvailabilityRule
{
    public static function assertActive(
        bool $active,
        string $catalogLabel,
        string $field,
        string $name,
    ): void {
        if (! $active) {
            throw new InactiveCatalogException($catalogLabel, $field, $name);
        }
    }
}
