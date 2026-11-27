<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use App\Modules\InscripcionesAtenciones\Exceptions\ModuloNoDisponibleException;

final class StubTurnosFuturosPaqueteContract implements TurnosFuturosPaqueteContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function resumen(int $paqueteId): TurnosFuturosPaqueteResumen
    {
        throw new ModuloNoDisponibleException('Agenda y turnos');
    }
}
