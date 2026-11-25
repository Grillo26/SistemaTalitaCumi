<?php

namespace App\Modules\Pacientes\Contracts;

/**
 * Consulta de la ficha de ingreso para 04.04 y consumidores internos.
 *
 * No expone contenido sensible ni datos del derivante.
 */
interface FichaIngresoDirectoryContract
{
    public function existe(int $pacienteId): bool;

    public function resumen(int $pacienteId): ?FichaIngresoResumen;

    public function tieneFichaCompleta(int $pacienteId): bool;

    public function fichaVigenteId(int $pacienteId): ?int;
}
