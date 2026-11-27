<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use App\Modules\InscripcionesAtenciones\Exceptions\ModuloNoDisponibleException;

final class StubConsumoPaqueteContract implements ConsumoPaqueteContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function resumen(int $paqueteId): ConsumoPaqueteResumen
    {
        throw new ModuloNoDisponibleException('Agenda y turnos');
    }
}
