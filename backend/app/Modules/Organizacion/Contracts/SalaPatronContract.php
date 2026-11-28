<?php

namespace App\Modules\Organizacion\Contracts;

/**
 * Patrón de días y hora de una sala grupal para la generación de turnos.
 *
 * El schema actual de `sala` solo guarda la franja Mañana/Tarde, sin días ni
 * hora exacta. Hasta que Salas exponga el patrón, el binding apunta al stub.
 */
interface SalaPatronContract
{
    public function patron(int $salaId): ?SalaPatronResumen;
}
