<?php

namespace App\Modules\AgendaTurnos\Actions;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;
use App\Modules\AgendaTurnos\Support\EntradaGeneracion;
use App\Modules\AgendaTurnos\Support\LoteResultado;

/**
 * [Programación de Turnos] RF-002.
 *
 * Caso de uso: generar los turnos de un paquete de sesiones individuales.
 */
final class GenerarTurnosDesdePaquete
{
    public function __construct(private readonly GenerarTurnosDesdeOrigen $generar) {}

    public function __invoke(int $paqueteId, EntradaGeneracion $entrada, User $actor): LoteResultado
    {
        return ($this->generar)(
            TipoOrigenTurno::PAQUETE_SESIONES,
            $paqueteId,
            $entrada,
            $actor,
        );
    }
}
