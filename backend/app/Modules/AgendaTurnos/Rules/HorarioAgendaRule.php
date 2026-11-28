<?php

namespace App\Modules\AgendaTurnos\Rules;

/**
 * 07.05/RF-002 / 07.05/RF-003 / 07.05/RN-003.
 *
 * Valida que el turno quede dentro del horario de atención configurado
 * (`agenda.horario_apertura` / `agenda.horario_cierre`) y que el rango sea
 * coherente. No consulta la base de datos.
 */
final class HorarioAgendaRule
{
    /**
     * @return list<string>
     */
    public function errores(
        string $horaInicio,
        string $horaFin,
        string $apertura,
        string $cierre,
    ): array {
        $inicio = $this->minutos($horaInicio);
        $fin = $this->minutos($horaFin);
        $abre = $this->minutos($apertura);
        $cierra = $this->minutos($cierre);

        if ($inicio === null || $fin === null) {
            return ['La hora del turno no es válida.'];
        }

        if ($abre === null || $cierra === null || $cierra <= $abre) {
            return ['El horario de atención configurado no es válido.'];
        }

        if ($fin <= $inicio) {
            return ['La hora de fin debe ser posterior a la hora de inicio.'];
        }

        if ($inicio < $abre || $fin > $cierra) {
            return [sprintf(
                'El turno debe estar entre %s y %s.',
                $this->formatear($abre),
                $this->formatear($cierra),
            )];
        }

        return [];
    }

    private function minutos(string $hora): ?int
    {
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $hora, $partes) !== 1) {
            return null;
        }

        return ((int) $partes[1]) * 60 + (int) $partes[2];
    }

    private function formatear(int $minutos): string
    {
        return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
    }
}
