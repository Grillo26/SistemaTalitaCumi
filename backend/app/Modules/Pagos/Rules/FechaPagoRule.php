<?php

namespace App\Modules\Pagos\Rules;

use App\Modules\Pagos\Exceptions\PagoFechaFuturaNoAutorizadaException;
use App\Modules\Pagos\Exceptions\PagoFechaInvalidaException;
use DateTimeImmutable;

/**
 * Fecha del pago (08.01/RF-016, RN-009).
 *
 * `hoy` llega calculado con `zona_horaria`. Las fechas pasadas se permiten
 * (el cierre de caja de 8.3 decide). Una fecha futura exige el permiso de
 * autorización y un motivo.
 */
final class FechaPagoRule
{
    public const MOTIVO_MAXIMO = 500;

    public static function assertValida(
        string $fecha,
        string $hoy,
        bool $puedeAutorizarFutura,
        ?string $motivoFuturo,
    ): bool {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

        if ($parsed === false || $parsed->format('Y-m-d') !== $fecha) {
            throw new PagoFechaInvalidaException;
        }

        if ($fecha <= $hoy) {
            return false;
        }

        if (! $puedeAutorizarFutura) {
            throw new PagoFechaFuturaNoAutorizadaException;
        }

        $motivo = trim((string) $motivoFuturo);

        if ($motivo === '' || mb_strlen($motivo) > self::MOTIVO_MAXIMO) {
            throw new PagoFechaInvalidaException(
                'Para una fecha futura el motivo es obligatorio (máximo '.self::MOTIVO_MAXIMO.' caracteres).',
            );
        }

        return true;
    }
}
