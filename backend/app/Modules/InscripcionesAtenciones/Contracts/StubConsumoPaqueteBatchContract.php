<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use App\Modules\InscripcionesAtenciones\Exceptions\ModuloNoDisponibleException;

final class StubConsumoPaqueteBatchContract implements ConsumoPaqueteBatchContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function resumenes(array $paqueteIds): array
    {
        throw new ModuloNoDisponibleException('Agenda y turnos');
    }
}
