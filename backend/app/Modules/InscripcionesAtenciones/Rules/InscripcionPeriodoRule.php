<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\PeriodoInscripcionInvalidoException;
use App\Modules\InscripcionesAtenciones\Support\InscripcionPeriod;
use Carbon\CarbonImmutable;

/**
 * RN-001: período mensual cerrado.
 *
 * Regla pura: no consulta base de datos, Request ni facades.
 */
final class InscripcionPeriodoRule
{
    public static function assertValido(?string $fechaInicio, ?string $fechaFin): void
    {
        if ($fechaInicio === null || $fechaInicio === '' || $fechaFin === null || $fechaFin === '') {
            throw new PeriodoInscripcionInvalidoException(
                'La fecha de inicio y la fecha de fin del período son obligatorias.',
            );
        }

        $inicio = self::parse($fechaInicio);

        if ($inicio === null) {
            throw new PeriodoInscripcionInvalidoException('La fecha de inicio no es válida.');
        }

        $fin = self::parse($fechaFin);

        if ($fin === null) {
            throw new PeriodoInscripcionInvalidoException('La fecha de fin no es válida.');
        }

        if ($fin->lt($inicio)) {
            throw new PeriodoInscripcionInvalidoException(
                'La fecha de fin no puede ser anterior a la fecha de inicio.',
            );
        }

        if ($fin->gt(CarbonImmutable::parse(InscripcionPeriod::maxEnd($fechaInicio)))) {
            throw new PeriodoInscripcionInvalidoException(
                'El período no puede superar un mes desde la fecha de inicio.',
            );
        }
    }

    private static function parse(string $fecha): ?CarbonImmutable
    {
        try {
            $parsed = CarbonImmutable::createFromFormat('!Y-m-d', $fecha);
        } catch (\Throwable) {
            return null;
        }

        if ($parsed === false || $parsed->format('Y-m-d') !== $fecha) {
            return null;
        }

        return $parsed;
    }
}
