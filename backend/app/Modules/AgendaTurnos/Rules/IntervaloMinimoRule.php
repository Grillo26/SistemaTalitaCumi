<?php

namespace App\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Support\TurnoExistente;

/**
 * 07.05/RF-002 / 07.05/RF-003 / 07.05/RN-003.
 *
 * Exige el intervalo mínimo configurado entre turnos del mismo profesional
 * (`agenda.intervalo_minimo_entre_turnos_minutos`). Los turnos contiguos solo
 * son válidos cuando el intervalo es 0.
 */
final class IntervaloMinimoRule
{
    /**
     * @param  list<TurnoExistente>  $existentes
     * @return list<string>
     */
    public function errores(
        int $profesionalId,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        array $existentes,
        int $minutos,
    ): array {
        if ($minutos <= 0) {
            return [];
        }

        $inicio = $this->minutos($horaInicio);
        $fin = $this->minutos($horaFin);

        if ($inicio === null || $fin === null) {
            return [];
        }

        foreach ($existentes as $existente) {
            if ($existente->profesionalId !== $profesionalId || $existente->fecha !== $fecha) {
                continue;
            }

            $existenteInicio = $this->minutos($existente->horaInicio);
            $existenteFin = $this->minutos($existente->horaFin);

            if ($existenteInicio === null || $existenteFin === null) {
                continue;
            }

            $separacion = $this->separacion($inicio, $fin, $existenteInicio, $existenteFin);

            if ($separacion !== null && $separacion < $minutos) {
                return [sprintf(
                    'Debe haber al menos %d minutos entre turnos del mismo profesional.',
                    $minutos,
                )];
            }
        }

        return [];
    }

    private function separacion(int $inicio, int $fin, int $existenteInicio, int $existenteFin): ?int
    {
        if ($inicio >= $existenteFin) {
            return $inicio - $existenteFin;
        }

        if ($fin <= $existenteInicio) {
            return $existenteInicio - $fin;
        }

        return null;
    }

    private function minutos(string $hora): ?int
    {
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $hora, $partes) !== 1) {
            return null;
        }

        return ((int) $partes[1]) * 60 + (int) $partes[2];
    }
}
