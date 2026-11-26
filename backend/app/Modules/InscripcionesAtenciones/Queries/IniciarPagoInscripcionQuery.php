<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\PagoInscripcionContract;
use App\Modules\InscripcionesAtenciones\Contracts\RegistroPagoInscripcion;

/**
 * Inicio del registro de pago desde la ficha (RF-013).
 */
final class IniciarPagoInscripcionQuery
{
    public function __construct(
        private readonly GetInscripcionQuery $detalle,
        private readonly PagoInscripcionContract $pagos,
    ) {}

    public function __invoke(int $id, User $actor): RegistroPagoInscripcion
    {
        ($this->detalle)($id, $actor);

        return $this->pagos->iniciarRegistroPago($id);
    }
}
