<?php

namespace App\Modules\Pacientes\Contracts;

/**
 * Estado de pago y próximo vencimiento (módulo 08).
 */
interface PatientPaymentStatusContract
{
    public function disponible(): bool;

    /**
     * @param  list<int>|null  $sucursalesAccesibles
     */
    public function estadoPago(
        int $pacienteId,
        ?array $sucursalesAccesibles = null,
        ?int $sucursalContexto = null,
    ): EstadoPagoResumen;
}
