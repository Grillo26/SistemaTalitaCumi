<?php

namespace App\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Support\ResumenSemana;

/**
 * [Agenda Semanal] RF-014, RNF-001.
 *
 * Cuenta los estados sobre un conjunto ya consultado, sin tocar la base.
 */
final class ResumenSemanaRule
{
    /**
     * @param  list<EstadoTurno|string>  $estados
     */
    public function __invoke(array $estados): ResumenSemana
    {
        $conteo = [];

        foreach (EstadoTurno::cases() as $estado) {
            $conteo[$estado->value] = 0;
        }

        $total = 0;

        foreach ($estados as $estado) {
            $value = $estado instanceof EstadoTurno ? $estado->value : (string) $estado;

            if (! array_key_exists($value, $conteo)) {
                continue;
            }

            $conteo[$value]++;
            $total++;
        }

        return new ResumenSemana(total: $total, porEstado: $conteo);
    }
}
