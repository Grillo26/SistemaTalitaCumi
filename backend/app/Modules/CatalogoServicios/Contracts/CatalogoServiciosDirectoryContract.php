<?php

namespace App\Modules\CatalogoServicios\Contracts;

/**
 * Directorio de lectura del catálogo para otros módulos (RF-C15).
 *
 * Las implementaciones deben ser fail-closed y no devolver datos inventados.
 */
interface CatalogoServiciosDirectoryContract
{
    /**
     * @return list<AreaResumen>
     */
    public function areas(bool $soloActivas = true): array;

    /**
     * @return list<ServicioResumen>
     */
    public function servicios(
        ?int $areaId = null,
        ?int $tipoServicioId = null,
        bool $soloActivos = true,
    ): array;

    public function servicio(int $servicioId): ?ServicioResumen;

    /**
     * @throws \App\Modules\CatalogoServicios\Exceptions\ServicioNotFoundException
     * @throws \App\Modules\CatalogoServicios\Exceptions\ServicioInactivoException
     */
    public function asegurarServicioActivo(int $servicioId): void;

    /**
     * @return list<TipoServicioResumen>
     */
    public function tiposServicio(bool $soloActivos = true): array;
}
