<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

/**
 * RN-004 / RN-011: saldo derivado de turnos.
 *
 * Asistió y Faltó consumen; Cancelado no consume. No se usan contadores
 * almacenados para evitar la inconsistencia de RT-001.
 */
final class SaldoSesionesRule
{
    public static function calcular(int $pactadas, int $realizadas, int $faltadas): int
    {
        return max(0, $pactadas - $realizadas - $faltadas);
    }
}
