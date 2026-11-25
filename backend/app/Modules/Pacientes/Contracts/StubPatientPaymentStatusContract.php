<?php

namespace App\Modules\Pacientes\Contracts;

use LogicException;

/**
 * Stub de estado de pago mientras no exista el módulo 08.
 */
final class StubPatientPaymentStatusContract implements PatientPaymentStatusContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function estadoPago(
        int $pacienteId,
        ?array $sucursalesAccesibles = null,
        ?int $sucursalContexto = null,
    ): EstadoPagoResumen {
        throw new LogicException('Módulo 08 — Pagos y caja no implementado todavía.');
    }
}
