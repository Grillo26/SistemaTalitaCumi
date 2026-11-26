<?php

namespace App\Modules\CatalogoServicios\Listeners;

use App\Modules\CatalogoServicios\Support\CatalogoServiciosCacheKeys;
use Illuminate\Support\Facades\Cache;

/**
 * Invalida el directorio cacheado (RF-C15 / RNF-002).
 */
final class InvalidateCatalogoServiciosCache
{
    public function handle(object $event): void
    {
        foreach (CatalogoServiciosCacheKeys::all() as $key) {
            Cache::forget($key);
        }
    }
}
