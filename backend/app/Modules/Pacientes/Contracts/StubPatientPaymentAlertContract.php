<?php

namespace App\Modules\Pacientes\Contracts;

use LogicException;

/**
 * Stub de alerta de próximo pago mientras no exista el módulo 09.
 */
final class StubPatientPaymentAlertContract implements PatientPaymentAlertContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function generarAlerta(
        int $pacienteId,
        ?array $sucursalesAccesibles = null,
    ): AlertaPagoResumen {
        throw new LogicException('Módulo 09 — Alertas de próximo pago no implementado todavía.');
    }
}
