<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

/**
 * Lectura de consumo de varios paquetes para panel y reportes.
 *
 * Extiende la lectura individual mediante composición para no modificar el
 * contrato existente ni romper implementaciones de Agenda.
 */
interface ConsumoPaqueteBatchContract
{
    public function disponible(): bool;

    /**
     * @param  list<int>  $paqueteIds
     * @return array<int, ConsumoPaqueteResumen>
     */
    public function resumenes(array $paqueteIds): array;
}
