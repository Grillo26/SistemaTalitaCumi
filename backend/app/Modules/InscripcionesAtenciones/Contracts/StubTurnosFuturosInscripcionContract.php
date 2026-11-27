<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use App\Modules\InscripcionesAtenciones\Exceptions\ModuloNoDisponibleException;

final class StubTurnosFuturosInscripcionContract implements TurnosFuturosInscripcionContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function resumen(int $inscripcionId): TurnosFuturosInscripcionResumen
    {
        throw new ModuloNoDisponibleException('Agenda y turnos');
    }
}
