<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\EstadoPagoInscripcion;
use App\Modules\InscripcionesAtenciones\Contracts\PagoInscripcionContract;

/**
 * Estado de pago de la inscripción, en endpoint separado de la ficha.
 */
final class GetPagoInscripcionQuery
{
    public function __construct(
        private readonly GetInscripcionQuery $detalle,
        private readonly PagoInscripcionContract $pagos,
    ) {}

    public function __invoke(int $id, User $actor): EstadoPagoInscripcion
    {
        ($this->detalle)($id, $actor);

        return $this->pagos->estadoPago($id);
    }
}
