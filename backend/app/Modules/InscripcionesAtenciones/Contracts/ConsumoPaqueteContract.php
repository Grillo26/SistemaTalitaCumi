<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

/**
 * Consumo de turnos de un paquete (RF-009).
 *
 * La implementación real pertenece al módulo 07. Mientras no exista, el stub
 * responde 503 y la ficha del paquete no se ve afectada.
 */
interface ConsumoPaqueteContract
{
    public function disponible(): bool;

    public function resumen(int $paqueteId): ConsumoPaqueteResumen;
}
