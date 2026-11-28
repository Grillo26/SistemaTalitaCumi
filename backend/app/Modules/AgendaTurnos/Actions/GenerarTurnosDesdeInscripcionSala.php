<?php

namespace App\Modules\AgendaTurnos\Actions;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;
use App\Modules\AgendaTurnos\Support\EntradaGeneracion;
use App\Modules\AgendaTurnos\Support\LoteResultado;

/**
 * [Programación de Turnos] RF-001.
 *
 * Caso de uso: generar los turnos de una inscripción a sala grupal. Requiere
 * que Organización exponga el patrón de días y hora de la sala; mientras el
 * contrato sea un stub, responde con un error claro.
 */
final class GenerarTurnosDesdeInscripcionSala
{
    public function __construct(private readonly GenerarTurnosDesdeOrigen $generar) {}

    public function __invoke(int $inscripcionId, EntradaGeneracion $entrada, User $actor): LoteResultado
    {
        return ($this->generar)(
            TipoOrigenTurno::INSCRIPCION_SALA,
            $inscripcionId,
            $entrada,
            $actor,
        );
    }
}
