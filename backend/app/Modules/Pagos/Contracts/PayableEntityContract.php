<?php

namespace App\Modules\Pagos\Contracts;

use App\Modules\Pagos\Enums\TipoEntidadPago;
use App\Modules\Pagos\Exceptions\PagoEntidadNoEncontradaException;
use App\Modules\Pagos\Exceptions\PagoModuloNoDisponibleException;

/**
 * Puerto de lectura de entidades pagables (08.01/RN-007, RF-001, RF-006).
 *
 * La implementación real pertenece a los módulos que originan la entidad
 * (06 para inscripciones, paquetes, entrevistas y diagnósticos; 10.3 para
 * ventas). Mientras no esté conectada, el stub responde 503 y el registro
 * de pagos no crea nada.
 */
interface PayableEntityContract
{
    public function disponible(): bool;

    /**
     * @throws PagoEntidadNoEncontradaException
     * @throws PagoModuloNoDisponibleException
     */
    public function resumen(TipoEntidadPago $tipo, int $entidadId): PayableEntityResumen;

    /**
     * Búsqueda por paciente, CI o número de entidad (RF-006), limitada a las
     * sucursales accesibles.
     *
     * @param  list<int>  $sucursalesAccesibles
     * @return list<PayableEntityResumen>
     *
     * @throws PagoModuloNoDisponibleException
     */
    public function buscar(string $termino, array $sucursalesAccesibles): array;
}
