<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;

/**
 * [Vigencia y Renovación] RF-007 / RN-006 / CA-006.
 *
 * Consumido por Agenda. Recibe la fecha del turno, no la fecha actual.
 */
interface VigenciaEntidadContract
{
    public function puedeRegistrarAsistencia(
        TipoEntidadVigencia $tipo,
        int $entidadId,
        string $fechaTurno,
    ): bool;
}
