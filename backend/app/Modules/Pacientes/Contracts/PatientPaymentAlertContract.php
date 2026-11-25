<?php

namespace App\Modules\Pacientes\Contracts;

/**
 * Alerta de próximo pago y enlace de WhatsApp (módulo 09).
 */
interface PatientPaymentAlertContract
{
    public function disponible(): bool;

    /**
     * @param  list<int>|null  $sucursalesAccesibles
     */
    public function generarAlerta(
        int $pacienteId,
        ?array $sucursalesAccesibles = null,
    ): AlertaPagoResumen;
}
