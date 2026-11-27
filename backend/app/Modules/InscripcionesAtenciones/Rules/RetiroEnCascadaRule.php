<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcion;

/**
 * [Estados Paciente e Inscripción] RF-005 / CU-02 / CA-006.
 */
final class RetiroEnCascadaRule
{
    public static function arrastra(EstadoInscripcion $estadoEfectivo): bool
    {
        return in_array(
            $estadoEfectivo,
            [EstadoInscripcion::ACTIVA, EstadoInscripcion::EN_PAUSA],
            true,
        );
    }
}
