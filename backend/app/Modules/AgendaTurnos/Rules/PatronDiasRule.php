<?php

namespace App\Modules\AgendaTurnos\Rules;

/**
 * [Programación de Turnos] RN-001 / RF-015.
 *
 * Valida el patrón de días y hora de una entidad origen. Regla pura: no
 * consulta la base de datos, no usa `now()` y no conoce Requests.
 */
final class PatronDiasRule
{
    /**
     * @param  list<int|string>  $diasSemana
     * @return list<string> errores encontrados; vacío si el patrón es válido
     */
    public function errores(array $diasSemana, string $horaInicio, int $duracionMinutos): array
    {
        $errores = [];
        $dias = array_map(static fn (int|string $dia): int => (int) $dia, $diasSemana);

        if ($dias === []) {
            $errores[] = 'Seleccioná al menos un día de la semana.';
        }

        if (array_filter($dias, static fn (int $dia): bool => $dia < 1 || $dia > 6) !== []) {
            $errores[] = 'Los días permitidos son de lunes a sábado.';
        }

        if (count($dias) !== count(array_unique($dias))) {
            $errores[] = 'No se puede repetir un día de la semana.';
        }

        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $horaInicio) !== 1) {
            $errores[] = 'La hora de inicio no es válida.';
        }

        if ($duracionMinutos <= 0) {
            $errores[] = 'La duración del turno debe ser mayor a cero.';
        }

        if ($errores === [] && $this->minutos($horaInicio) + $duracionMinutos > 24 * 60) {
            $errores[] = 'El turno no puede terminar después de las 24:00.';
        }

        return $errores;
    }

    /**
     * @param  list<int|string>  $diasSemana
     */
    public function esValido(array $diasSemana, string $horaInicio, int $duracionMinutos): bool
    {
        return $this->errores($diasSemana, $horaInicio, $duracionMinutos) === [];
    }

    private function minutos(string $hora): int
    {
        $partes = explode(':', $hora);

        return ((int) ($partes[0] ?? 0)) * 60 + ((int) ($partes[1] ?? 0));
    }
}
