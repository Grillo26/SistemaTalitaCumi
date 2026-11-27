<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

/**
 * Estado de pago y registro de pago desde la ficha (RF-013).
 *
 * La implementación real pertenece al módulo 08. Mientras no exista, el
 * stub responde 503 y la ficha principal no se ve afectada.
 */
interface PagoInscripcionContract
{
    public function disponible(): bool;

    public function estadoPago(int $inscripcionId): EstadoPagoInscripcion;

    public function iniciarRegistroPago(int $inscripcionId): RegistroPagoInscripcion;
}
