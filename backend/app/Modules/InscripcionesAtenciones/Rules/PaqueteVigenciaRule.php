<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\PaquetePeriodoInvalidoException;
use Carbon\CarbonImmutable;

/**
 * Vigencia del paquete: fechas obligatorias y fin >= inicio.
 */
final class PaqueteVigenciaRule
{
    public static function assertValido(?string $fechaInicio, ?string $fechaFin): void
    {
        if ($fechaInicio === null || $fechaInicio === '' || $fechaFin === null || $fechaFin === '') {
            throw new PaquetePeriodoInvalidoException(
                'La fecha de inicio y la fecha de fin del paquete son obligatorias.',
            );
        }

        $inicio = self::parse($fechaInicio);

        if ($inicio === null) {
            throw new PaquetePeriodoInvalidoException('La fecha de inicio no es válida.');
        }

        $fin = self::parse($fechaFin);

        if ($fin === null) {
            throw new PaquetePeriodoInvalidoException('La fecha de fin no es válida.');
        }

        if ($fin->lt($inicio)) {
            throw new PaquetePeriodoInvalidoException(
                'La fecha de fin no puede ser anterior a la fecha de inicio.',
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
