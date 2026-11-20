<?php

namespace App\Modules\Organizacion\Contracts;

use App\Modules\Organizacion\Enums\ParameterCategory;
use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;
use App\Modules\Organizacion\Models\Parametro;
use App\Modules\Organizacion\Support\ParameterCacheKeys;
use App\Modules\Organizacion\Support\ParameterValue;
use App\Modules\Organizacion\Support\ParameterValueCaster;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Implementación cacheada del contrato de lectura (RNF-002).
 *
 * Usa el store configurado (file en esta etapa). No usa tags porque el store
 * file no las soporta; la invalidación es explícita por clave y categoría.
 */
final class EloquentParameterReader implements ParameterReaderContract
{
    public function obtener(string $clave): ParameterValue
    {
        $cacheKey = ParameterCacheKeys::forParameter($clave);

        $cached = Cache::get($cacheKey);

        if ($cached instanceof ParameterValue) {
            return $cached;
        }

        $parametro = Parametro::query()->where('clave', $clave)->first();

        if ($parametro === null) {
            Log::warning('Parámetro de configuración inexistente solicitado por un módulo consumidor.', [
                'clave' => $clave,
            ]);

            throw new ParameterNotFoundException($clave);
        }

        $value = self::toValue($parametro);

        Cache::put($cacheKey, $value, ParameterCacheKeys::TTL_SECONDS);

        return $value;
    }

    public function obtenerVarios(array $claves): array
    {
        $values = [];

        foreach ($claves as $clave) {
            $values[$clave] = $this->obtener($clave);
        }

        return $values;
    }

    public function porCategoria(string $categoria): array
    {
        $cacheKey = ParameterCacheKeys::forCategory($categoria);

        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $values = Parametro::query()
            ->where('categoria', $categoria)
            ->orderBy('clave')
            ->get()
            ->mapWithKeys(fn (Parametro $parametro): array => [
                $parametro->clave => self::toValue($parametro),
            ])
            ->all();

        Cache::put($cacheKey, $values, ParameterCacheKeys::TTL_SECONDS);

        return $values;
    }

    private static function toValue(Parametro $parametro): ParameterValue
    {
        return new ParameterValue(
            clave: $parametro->clave,
            valor: ParameterValueCaster::cast($parametro->tipo, $parametro->valor),
            tipo: $parametro->tipo,
            unidad: $parametro->unidad,
            categoria: $parametro->categoria ?? ParameterCategory::OPERACION,
            sensible: $parametro->sensible,
            visibleRecepcion: $parametro->visible_recepcion,
            actualizadoEn: $parametro->updated_at?->toIso8601String(),
        );
    }
}
