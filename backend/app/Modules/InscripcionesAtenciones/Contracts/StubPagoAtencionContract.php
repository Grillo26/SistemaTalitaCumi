<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use App\Modules\InscripcionesAtenciones\Enums\TipoAtencion;
use App\Modules\InscripcionesAtenciones\Exceptions\ModuloNoDisponibleException;

final class StubPagoAtencionContract implements PagoAtencionContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function estadoPago(TipoAtencion $tipo, int $atencionId): EstadoPagoInscripcion
    {
        throw new ModuloNoDisponibleException('Pagos y caja');
    }

    public function iniciarRegistroPago(TipoAtencion $tipo, int $atencionId): RegistroPagoInscripcion
    {
        throw new ModuloNoDisponibleException('Pagos y caja');
    }
}
