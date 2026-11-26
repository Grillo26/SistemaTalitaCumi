<?php

namespace App\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\TarifaCerradaException;
use App\Modules\CatalogoServicios\Exceptions\TarifaVigenciaInvalidaException;
use App\Modules\CatalogoServicios\Exceptions\TarifaYaCerradaException;

/**
 * Reglas de vigencia de tarifas (RN-T03 / RF-T07 / RF-T11 / RF-T12).
 *
 * Las fechas son strings ISO Y-m-d; la comparación lexicográfica es válida.
 */
final class TarifaVigenciaRule
{
    public static function assertValid(?string $vigenteDesde, ?string $vigenteHasta): void
    {
        $desde = trim((string) $vigenteDesde);
        $hasta = $vigenteHasta === null ? null : trim((string) $vigenteHasta);

        if (! self::isDate($desde)) {
            throw new TarifaVigenciaInvalidaException('vigente_desde');
        }

        if ($hasta !== null && $hasta !== '' && ! self::isDate($hasta)) {
            throw new TarifaVigenciaInvalidaException('vigente_hasta');
        }

        if ($hasta !== null && $hasta !== '' && $hasta < $desde) {
            throw new TarifaVigenciaInvalidaException('vigente_hasta');
        }
    }

    public static function assertEditable(?string $vigenteHasta, ?string $today = null): void
    {
        $today ??= now()->toDateString();
        $hasta = $vigenteHasta === null ? null : trim((string) $vigenteHasta);

        if ($hasta !== null && $hasta !== '' && $hasta < $today) {
            throw new TarifaCerradaException;
        }
    }

    public static function assertCanClose(
        ?string $vigenteHastaActual,
        string $nuevoVigenteHasta,
        string $vigenteDesde,
    ): void {
        if ($vigenteHastaActual !== null) {
            throw new TarifaYaCerradaException;
        }

        self::assertValid($vigenteDesde, $nuevoVigenteHasta);
    }

    public static function isDate(string $value): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return false;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
