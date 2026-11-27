<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

/**
 * Lectura de la entidad origen de una programación de turnos (módulo 07).
 *
 * La implementación vive en Inscripciones porque ese módulo es dueño de los
 * datos y del estado efectivo. Agenda nunca lee sus modelos directamente.
 */
interface OrigenTurnosContract
{
    /**
     * @param  string  $tipo  `inscripcion_sala` o `paquete_sesiones`
     */
    public function obtener(string $tipo, int $id): ?OrigenTurnosResumen;
}
