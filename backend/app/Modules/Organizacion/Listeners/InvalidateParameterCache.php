<?php

namespace App\Modules\Organizacion\Listeners;

use App\Modules\Organizacion\Events\ParameterCreated;
use App\Modules\Organizacion\Events\ParameterRestored;
use App\Modules\Organizacion\Events\ParameterUpdated;
use App\Modules\Organizacion\Support\ParameterCacheKeys;
use Illuminate\Support\Facades\Cache;

/**
 * Invalida la caché de lectura al crear, editar o restaurar (RNF-002).
 *
 * El store file no soporta tags: se olvidan explícitamente la clave del
 * parámetro y la de su categoría.
 */
final class InvalidateParameterCache
{
    public function handle(ParameterCreated|ParameterUpdated|ParameterRestored $event): void
    {
        $snapshot = $event instanceof ParameterCreated ? $event->snapshot : $event->after;

        $clave = $snapshot['clave'] ?? null;
        $categoria = $snapshot['categoria'] ?? null;

        if (is_string($clave) && $clave !== '') {
            Cache::forget(ParameterCacheKeys::forParameter($clave));
        }

        if (is_string($categoria) && $categoria !== '') {
            Cache::forget(ParameterCacheKeys::forCategory($categoria));
        }
    }
}
