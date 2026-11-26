<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Enums\DiaSemana;
use App\Modules\InscripcionesAtenciones\Exceptions\DiasSemanaInvalidosException;
use App\Modules\InscripcionesAtenciones\Exceptions\HoraInvalidaException;

/**
 * RN-002: uno o más días lunes-sábado, sin repetidos, y hora fija válida.
 */
final class DiasHoraRule
{
    /**
     * @param  array<int, mixed>  $dias
     */
    public static function assertDiasValidos(array $dias): string
    {
        if ($dias === []) {
            throw new DiasSemanaInvalidosException('Seleccioná al menos un día de la semana.');
        }

        $normalizados = array_map(
            static fn (mixed $dia): int => is_numeric($dia) ? (int) $dia : -1,
            $dias,
        );

        if (count(array_unique($normalizados)) !== count($normalizados)) {
            throw new DiasSemanaInvalidosException('No se puede repetir el mismo día de la semana.');
        }

        foreach ($normalizados as $dia) {
            if (! in_array($dia, DiaSemana::values(), true)) {
                throw new DiasSemanaInvalidosException(
                    'Los días deben estar entre lunes (1) y sábado (6).',
                );
            }
        }

        sort($normalizados);

        return implode(',', $normalizados);
    }

    public static function assertHoraValida(string $hora): string
    {
        $hora = trim($hora);

        if (! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora)) {
            throw new HoraInvalidaException;
        }

        return $hora;
    }
}
