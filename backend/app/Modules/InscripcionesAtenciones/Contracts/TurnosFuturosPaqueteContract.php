<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

/**
 * Pre-chequeo de turnos futuros de un paquete (CU-03/FE-01 y RF-012).
 *
 * No forma parte de BajaPaquete ni de UpdatePaquete: ambos deben funcionar
 * sin Agenda.
 */
interface TurnosFuturosPaqueteContract
{
    public function disponible(): bool;

    public function resumen(int $paqueteId): TurnosFuturosPaqueteResumen;
}
