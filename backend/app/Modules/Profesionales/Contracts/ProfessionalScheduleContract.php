<?php

namespace App\Modules\Profesionales\Contracts;

use Carbon\CarbonImmutable;

/**
 * Consultas de agenda necesarias para el módulo 03 (03/RF-A04, 03/RT-09).
 *
 * La implementación real pertenece al módulo 07 — Agenda y turnos. Mientras
 * ese módulo no exista, el contenedor bindea un stub que lanza LogicException.
 */
interface ProfessionalScheduleContract
{
    /**
     * Indica si el profesional ya tiene un turno solapado en el rango.
     *
     * El rango se expresa en UTC. La implementación real debe comparar contra
     * turnos y diagnósticos de todas las sucursales y no debe exponer datos de
     * sucursales fuera del alcance del usuario en el mensaje de rechazo.
     */
    public function haySolapamiento(
        int $profesionalId,
        CarbonImmutable $inicio,
        CarbonImmutable $fin,
    ): bool;

    /**
     * Indica si el profesional tiene asignaciones futuras desde la fecha dada.
     *
     * Se usará para RT-09 antes de desactivar un profesional.
     */
    public function tieneAsignacionesFuturas(
        int $profesionalId,
        CarbonImmutable $desde,
    ): bool;
}
