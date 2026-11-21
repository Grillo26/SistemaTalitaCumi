<?php

namespace App\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Enums\EstadoFichaIngreso;
use App\Modules\Pacientes\Exceptions\FichaIngresoArchivedException;
use App\Modules\Pacientes\Exceptions\FichaIngresoStatusTransitionException;
use App\Modules\Pacientes\Models\FichaIngreso;

/**
 * Transiciones de estado de la ficha (04.03/RN-005, 04.03/RNF-007 bis).
 */
final class FichaIngresoStatusRule
{
    public function assertCanEdit(FichaIngreso $ficha): void
    {
        if ($ficha->estado === EstadoFichaIngreso::ARCHIVADA) {
            throw new FichaIngresoArchivedException;
        }
    }

    public function assertCanComplete(FichaIngreso $ficha): void
    {
        if ($ficha->estado === EstadoFichaIngreso::ARCHIVADA) {
            throw new FichaIngresoArchivedException;
        }

        if ($ficha->estado === EstadoFichaIngreso::COMPLETA) {
            throw new FichaIngresoStatusTransitionException('La ficha ya está completa.');
        }
    }

    public function assertCanArchive(FichaIngreso $ficha): void
    {
        if ($ficha->estado === EstadoFichaIngreso::ARCHIVADA) {
            throw new FichaIngresoStatusTransitionException('La ficha ya está archivada.');
        }
    }

    public function assertCanReactivate(FichaIngreso $ficha): void
    {
        if ($ficha->estado !== EstadoFichaIngreso::ARCHIVADA) {
            throw new FichaIngresoStatusTransitionException('Solo se puede reactivar una ficha archivada.');
        }
    }

    public function reactivationTarget(FichaIngreso $ficha): EstadoFichaIngreso
    {
        return $ficha->estado_anterior === EstadoFichaIngreso::COMPLETA->value
            ? EstadoFichaIngreso::COMPLETA
            : EstadoFichaIngreso::BORRADOR;
    }
}
