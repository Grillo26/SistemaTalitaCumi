<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

/**
 * Estado de pago y registro de pago de un paquete (RF-016).
 *
 * Reutiliza los DTO genéricos de pago de Salas; el contrato nuevo evita que
 * el método reciba un `inscripcionId` de otra tabla.
 */
interface PagoPaqueteContract
{
    public function disponible(): bool;

    public function estadoPago(int $paqueteId): EstadoPagoInscripcion;

    public function iniciarRegistroPago(int $paqueteId): RegistroPagoInscripcion;
}
