<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\PagoAtencionContract;
use App\Modules\InscripcionesAtenciones\Contracts\RegistroPagoInscripcion;
use App\Modules\InscripcionesAtenciones\Enums\TipoAtencion;

/**
 * Inicio del registro de pago de una atención (RF-013).
 */
final class IniciarPagoAtencionQuery
{
    public function __construct(
        private readonly GetAtencionQuery $detalle,
        private readonly PagoAtencionContract $pagos,
    ) {}

    public function __invoke(TipoAtencion $tipo, int $id, User $actor): RegistroPagoInscripcion
    {
        ($this->detalle)($tipo, $id, $actor);

        return $this->pagos->iniciarRegistroPago($tipo, $id);
    }
}
