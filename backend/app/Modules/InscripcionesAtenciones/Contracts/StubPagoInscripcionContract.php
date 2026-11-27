<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use App\Modules\InscripcionesAtenciones\Exceptions\ModuloNoDisponibleException;

final class StubPagoInscripcionContract implements PagoInscripcionContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function estadoPago(int $inscripcionId): EstadoPagoInscripcion
    {
        throw new ModuloNoDisponibleException('Pagos y caja');
    }

    public function iniciarRegistroPago(int $inscripcionId): RegistroPagoInscripcion
    {
        throw new ModuloNoDisponibleException('Pagos y caja');
    }
}
