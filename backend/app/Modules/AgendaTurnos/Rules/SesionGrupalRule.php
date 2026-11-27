<?php

namespace App\Modules\AgendaTurnos\Rules;

/**
 * [Programación de Turnos] RF-005 / RF-006 / CA-004.
 *
 * Una sesión grupal se identifica por sala + fecha + hora de inicio + hora de
 * fin + servicio. Varios turnos (uno por paciente) comparten la misma sesión:
 * para el solapamiento de profesional y de sala cuentan como uno solo.
 */
final class SesionGrupalRule
{
    public static function clave(
        ?int $salaId,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        int $servicioId,
    ): ?string {
        if ($salaId === null) {
            return null;
        }

        return implode('|', [
            $salaId,
            $fecha,
            $horaInicio,
            $horaFin,
            $servicioId,
        ]);
    }

    public static function misma(string $claveA, string $claveB): bool
    {
        return $claveA === $claveB;
    }
}
