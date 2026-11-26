<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use App\Modules\InscripcionesAtenciones\Enums\TipoAtencion;

/**
 * Estado de pago y registro de pago de una atención (RF-013).
 *
 * La implementación real pertenece al módulo 08. Mientras no exista, el
 * stub responde 503 y la ficha principal no se ve afectada.
 */
interface PagoAtencionContract
{
    public function disponible(): bool;

    public function estadoPago(TipoAtencion $tipo, int $atencionId): EstadoPagoInscripcion;

    public function iniciarRegistroPago(TipoAtencion $tipo, int $atencionId): RegistroPagoInscripcion;
}
