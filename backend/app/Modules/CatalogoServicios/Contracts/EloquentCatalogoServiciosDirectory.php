<?php

namespace App\Modules\CatalogoServicios\Contracts;

use App\Modules\CatalogoServicios\Exceptions\ServicioInactivoException;
use App\Modules\CatalogoServicios\Exceptions\ServicioNotFoundException;
use App\Modules\CatalogoServicios\Queries\CatalogoServiciosDirectoryQuery;

/**
 * Implementación del directorio con caché corta e invalidación por eventos.
 */
final class EloquentCatalogoServiciosDirectory implements CatalogoServiciosDirectoryContract
{
    public function __construct(private readonly CatalogoServiciosDirectoryQuery $query) {}

    public function areas(bool $soloActivas = true): array
    {
        return array_map(
            static fn (array $item): AreaResumen => AreaResumen::fromArray($item),
            $this->query->areas($soloActivas),
        );
    }

    public function servicios(
        ?int $areaId = null,
        ?int $tipoServicioId = null,
        bool $soloActivos = true,
    ): array {
        return array_map(
            static fn (array $item): ServicioResumen => ServicioResumen::fromArray($item),
            $this->query->servicios($areaId, $tipoServicioId, $soloActivos),
        );
    }

    public function servicio(int $servicioId): ?ServicioResumen
    {
        $item = $this->query->servicio($servicioId);

        return $item === null ? null : ServicioResumen::fromArray($item);
    }

    public function asegurarServicioActivo(int $servicioId): void
    {
        $servicio = $this->servicio($servicioId);

        if ($servicio === null) {
            throw new ServicioNotFoundException($servicioId);
        }

        if (! $servicio->activo) {
            throw new ServicioInactivoException($servicioId);
        }
    }

    public function tiposServicio(bool $soloActivos = true): array
    {
        return array_map(
            static fn (array $item): TipoServicioResumen => TipoServicioResumen::fromArray($item),
            $this->query->tiposServicio($soloActivos),
        );
    }
}
