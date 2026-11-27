<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\EstadoPagoInscripcion;
use App\Modules\InscripcionesAtenciones\Contracts\PagoAtencionContract;
use App\Modules\InscripcionesAtenciones\Enums\TipoAtencion;

/**
 * Estado de pago de una atención, en endpoint separado de la ficha (RF-013).
 */
final class GetPagoAtencionQuery
{
    public function __construct(
        private readonly GetAtencionQuery $detalle,
        private readonly PagoAtencionContract $pagos,
    ) {}

    public function __invoke(TipoAtencion $tipo, int $id, User $actor): EstadoPagoInscripcion
    {
        ($this->detalle)($tipo, $id, $actor);

        return $this->pagos->estadoPago($tipo, $id);
    }
}
