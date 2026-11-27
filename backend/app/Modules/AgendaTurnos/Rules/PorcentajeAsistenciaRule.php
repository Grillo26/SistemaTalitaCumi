<?php

namespace App\Modules\AgendaTurnos\Rules;

/**
 * 07.04/RF-009, 07.04/RF-015, 07.04/CU-03, 07.04/CA-008.
 *
 * PROPUESTO - VALIDAR: asistidos / (asistidos + faltos).
 * Cancelados, reprogramados y programados pendientes no entran al
 * denominador. Sin denominador el porcentaje es 0.
 */
final class PorcentajeAsistenciaRule
{
    public function calcular(int $asistidos, int $faltos): float
    {
        $total = max(0, $asistidos) + max(0, $faltos);

        if ($total === 0) {
            return 0.0;
        }

        return round((max(0, $asistidos) / $total) * 100, 2);
    }
}
