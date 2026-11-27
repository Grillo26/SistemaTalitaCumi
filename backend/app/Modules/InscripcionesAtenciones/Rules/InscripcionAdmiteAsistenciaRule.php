<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcion;

/**
 * [Estados Paciente e Inscripción] RF-008 / RN-007 / CA-004.
 *
 * Agenda consume esta regla a través del contrato de Vigencia. El mensaje
 * "La inscripción no está activa" pertenece a Agenda.
 */
final class InscripcionAdmiteAsistenciaRule
{
    public static function permite(EstadoInscripcion $estadoEfectivo): bool
    {
        return $estadoEfectivo->permiteAsistencia();
    }
}
