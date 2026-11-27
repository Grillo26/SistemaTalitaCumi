<?php

namespace App\Modules\AgendaTurnos\Rules;

/**
 * [Programación de Turnos] RF-009 / RN-003 / RNF-011.
 *
 * La duración es siempre un entero positivo. El valor por defecto lo aporta
 * la configuración del sistema o la entidad origen; esta regla nunca
 * hardcodea minutos.
 */
final class DuracionTurnoRule
{
    public function esValida(int $duracionMinutos): bool
    {
        return $duracionMinutos > 0;
    }

    /**
     * Primer candidato positivo en orden de prioridad (entidad, servicio,
     * configuración). Devuelve null si ninguno es válido: la Action decide el
     * error controlado.
     */
    public function resolver(?int ...$candidatos): ?int
    {
        foreach ($candidatos as $candidato) {
            if ($candidato !== null && $candidato > 0) {
                return $candidato;
            }
        }

        return null;
    }
}
