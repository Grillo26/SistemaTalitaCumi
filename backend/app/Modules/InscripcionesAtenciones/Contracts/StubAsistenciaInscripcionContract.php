<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use App\Modules\InscripcionesAtenciones\Exceptions\ModuloNoDisponibleException;

final class StubAsistenciaInscripcionContract implements AsistenciaInscripcionContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function resumen(int $inscripcionId): AsistenciaInscripcionResumen
    {
        throw new ModuloNoDisponibleException('Agenda y turnos');
    }
}
