<?php

namespace App\Modules\AgendaTurnos\Rules;

/**
 * [Reprogramación y Cancelación] RF-008 / RN-006 / CA-005.
 *
 * La decisión de consumir saldo sale del flag configurado del motivo. Si el
 * motivo no lo define, no consume: nunca se asume un efecto por el origen.
 */
final class EfectoSaldoRule
{
    public function consume(?bool $descuentaSaldo): bool
    {
        return $descuentaSaldo === true;
    }

    public function descripcion(?bool $descuentaSaldo): string
    {
        return match ($descuentaSaldo) {
            true => 'La cancelación descuenta una sesión del saldo.',
            false => 'La cancelación no descuenta saldo.',
            null => 'El motivo no tiene configurado el efecto en saldo; no se descuenta.',
        };
    }
}
