<?php

namespace App\Modules\Profesionales\Contracts;

use Carbon\CarbonImmutable;
use LogicException;

/**
 * Stub de agenda mientras el módulo 07 no exista (03/Fase 3).
 *
 * No se usa todavía en las Actions de desactivación: llamarlo haría fallar la
 * operación. Documenta el límite y permite detectar usos accidentales.
 */
final class StubProfessionalScheduleContract implements ProfessionalScheduleContract
{
    public function haySolapamiento(
        int $profesionalId,
        CarbonImmutable $inicio,
        CarbonImmutable $fin,
    ): bool {
        throw new LogicException('Módulo 07 — Agenda y turnos no implementado todavía.');
    }

    public function tieneAsignacionesFuturas(
        int $profesionalId,
        CarbonImmutable $desde,
    ): bool {
        throw new LogicException('Módulo 07 — Agenda y turnos no implementado todavía.');
    }
}
