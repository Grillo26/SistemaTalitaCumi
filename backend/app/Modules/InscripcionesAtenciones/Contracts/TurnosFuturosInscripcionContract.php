<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

/**
 * Pre-chequeo de turnos futuros antes de la baja (CU-03/FE-01).
 *
 * No forma parte de BajaInscripcion: la baja debe funcionar sin Agenda.
 */
interface TurnosFuturosInscripcionContract
{
    public function disponible(): bool;

    public function resumen(int $inscripcionId): TurnosFuturosInscripcionResumen;
}
