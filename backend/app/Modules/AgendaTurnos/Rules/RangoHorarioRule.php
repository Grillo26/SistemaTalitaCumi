<?php

namespace App\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Exceptions\ConfiguracionAgendaIncompletaException;
use App\Modules\AgendaTurnos\Support\RangoHorario;

/**
 * [Agenda Semanal] RF-001, RNF-001.
 *
 * Calcula el rango visible de la grilla: parte de la apertura/cierre
 * configurados y lo expande si hay turnos fuera de ese horario. Nunca oculta
 * un turno por un rango fijo.
 */
final class RangoHorarioRule
{
    /**
     * @param  list<array{hora_inicio: string, hora_fin: string}>  $turnos
     */
    public function __invoke(string $apertura, string $cierre, array $turnos): RangoHorario
    {
        $inicio = $this->minutos($apertura, 'agenda.horario_apertura');
        $fin = $this->minutos($cierre, 'agenda.horario_cierre');

        if ($fin <= $inicio) {
            throw new ConfiguracionAgendaIncompletaException(
                'La hora de cierre de la agenda debe ser posterior a la hora de apertura.',
            );
        }

        foreach ($turnos as $turno) {
            $inicioTurno = $this->minutosOpcional($turno['hora_inicio'] ?? null);
            $finTurno = $this->minutosOpcional($turno['hora_fin'] ?? null);

            if ($inicioTurno === null || $finTurno === null || $finTurno <= $inicioTurno) {
                continue;
            }

            $inicio = min($inicio, $inicioTurno);
            $fin = max($fin, $finTurno);
        }

        $inicio = max(0, min(1440, $this->redondearAbajo($inicio)));
        $fin = max(0, min(1440, $this->redondearArriba($fin)));

        if ($fin <= $inicio) {
            $inicio = max(0, $inicio - 30);
            $fin = min(1440, $inicio + 30);
        }

        return new RangoHorario(
            horaInicio: $this->formatear($inicio),
            horaFin: $this->formatear($fin),
        );
    }

    private function minutos(string $hora, string $clave): int
    {
        $minutos = $this->minutosOpcional($hora);

        if ($minutos === null) {
            throw new ConfiguracionAgendaIncompletaException(
                "El parámetro {$clave} no tiene una hora válida.",
            );
        }

        return $minutos;
    }

    private function minutosOpcional(?string $hora): ?int
    {
        if ($hora === null || preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $hora) !== 1) {
            return null;
        }

        [$horas, $minutos] = array_pad(explode(':', $hora, 3), 2, '0');

        return ((int) $horas * 60) + (int) $minutos;
    }

    private function redondearAbajo(int $minutos): int
    {
        return intdiv($minutos, 30) * 30;
    }

    private function redondearArriba(int $minutos): int
    {
        return (int) (ceil($minutos / 30) * 30);
    }

    private function formatear(int $minutos): string
    {
        $horas = intdiv($minutos, 60);
        $resto = $minutos % 60;

        return sprintf('%02d:%02d', $horas, $resto);
    }
}
