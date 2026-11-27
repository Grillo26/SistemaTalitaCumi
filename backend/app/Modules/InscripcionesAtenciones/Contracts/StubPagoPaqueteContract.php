<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use App\Modules\InscripcionesAtenciones\Exceptions\ModuloNoDisponibleException;

final class StubPagoPaqueteContract implements PagoPaqueteContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function estadoPago(int $paqueteId): EstadoPagoInscripcion
    {
        throw new ModuloNoDisponibleException('Pagos y caja');
    }

    public function iniciarRegistroPago(int $paqueteId): RegistroPagoInscripcion
    {
        throw new ModuloNoDisponibleException('Pagos y caja');
    }
}
