<?php

namespace App\Modules\Pagos\Contracts;

use App\Modules\Pagos\Enums\TipoEntidadPago;
use App\Modules\Pagos\Exceptions\PagoModuloNoDisponibleException;

/**
 * Stub de entidades pagables mientras 06/10.3 no estén conectados.
 *
 * Nunca devuelve datos falsos: `disponible()` es false y cada método falla
 * con 503 MODULO_NO_DISPONIBLE.
 */
final class StubPayableEntityContract implements PayableEntityContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function resumen(TipoEntidadPago $tipo, int $entidadId): PayableEntityResumen
    {
        throw new PagoModuloNoDisponibleException(
            'Entidades pagables (módulo 06 / 10.3)',
            'La entidad pagable todavía no está conectada con Pagos y caja (módulo 06 / 10.3).',
        );
    }

    public function buscar(string $termino, array $sucursalesAccesibles): array
    {
        throw new PagoModuloNoDisponibleException(
            'Entidades pagables (módulo 06 / 10.3)',
            'La búsqueda de entidades a pagar todavía no está conectada (módulo 06 / 10.3).',
        );
    }
}
