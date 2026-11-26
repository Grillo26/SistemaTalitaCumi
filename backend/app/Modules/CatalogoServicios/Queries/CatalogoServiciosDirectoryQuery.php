<?php

namespace App\Modules\CatalogoServicios\Queries;

use App\Modules\CatalogoServicios\Models\Area;
use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\CatalogoServicios\Models\TipoServicio;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosCacheKeys;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosModule;
use Illuminate\Support\Facades\Cache;

/**
 * Directorio cacheado para otros módulos (RF-C15).
 *
 * Cachea las listas completas (catálogos pequeños) y filtra en memoria.
 * La invalidación la hace InvalidateCatalogoServiciosCache.
 */
final class CatalogoServiciosDirectoryQuery
{
    /**
     * @return list<array<string, mixed>>
     */
    public function areas(bool $soloActivas = true): array
    {
        $items = Cache::remember(
            CatalogoServiciosCacheKeys::areas(),
            CatalogoServiciosModule::CACHE_TTL_SECONDS,
            static fn (): array => Area::query()
                ->orderBy('nombre')
                ->get()
                ->map(static fn (Area $area): array => [
                    'id' => $area->id,
                    'nombre' => $area->nombre,
                    'descripcion' => $area->descripcion,
                    'color' => $area->color,
                    'activo' => (bool) $area->activo,
                ])
                ->all(),
        );

        return $soloActivas
            ? array_values(array_filter($items, static fn (array $item): bool => $item['activo']))
            : $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function servicios(
        ?int $areaId = null,
        ?int $tipoServicioId = null,
        bool $soloActivos = true,
    ): array {
        $items = Cache::remember(
            CatalogoServiciosCacheKeys::servicios(),
            CatalogoServiciosModule::CACHE_TTL_SECONDS,
            static fn (): array => Servicio::query()
                ->with(['area', 'tipoServicio'])
                ->orderBy('nombre')
                ->get()
                ->map(static fn (Servicio $servicio): array => [
                    'id' => $servicio->id,
                    'area_id' => $servicio->area_id,
                    'area_nombre' => $servicio->area?->nombre,
                    'area_color' => $servicio->area?->color,
                    'tipo_servicio_id' => $servicio->tipo_servicio_id,
                    'tipo_servicio_nombre' => $servicio->tipoServicio?->nombre,
                    'nombre' => $servicio->nombre,
                    'descripcion' => $servicio->descripcion,
                    'duracion_minutos' => (int) $servicio->duracion_minutos,
                    'activo' => (bool) $servicio->activo,
                ])
                ->all(),
        );

        return array_values(array_filter(
            $items,
            static function (array $item) use ($areaId, $tipoServicioId, $soloActivos): bool {
                if ($soloActivos && ! $item['activo']) {
                    return false;
                }

                if ($areaId !== null && $item['area_id'] !== $areaId) {
                    return false;
                }

                if ($tipoServicioId !== null && $item['tipo_servicio_id'] !== $tipoServicioId) {
                    return false;
                }

                return true;
            },
        ));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function servicio(int $servicioId): ?array
    {
        foreach ($this->servicios(null, null, false) as $servicio) {
            if ($servicio['id'] === $servicioId) {
                return $servicio;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function tiposServicio(bool $soloActivos = true): array
    {
        $items = Cache::remember(
            CatalogoServiciosCacheKeys::tiposServicio(),
            CatalogoServiciosModule::CACHE_TTL_SECONDS,
            static fn (): array => TipoServicio::query()
                ->orderBy('nombre')
                ->get()
                ->map(static fn (TipoServicio $tipo): array => [
                    'id' => $tipo->id,
                    'nombre' => $tipo->nombre,
                    'activo' => (bool) $tipo->activo,
                ])
                ->all(),
        );

        return $soloActivos
            ? array_values(array_filter($items, static fn (array $item): bool => $item['activo']))
            : $items;
    }
}
