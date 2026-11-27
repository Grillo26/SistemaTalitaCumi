<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\TurnosFuturosInscripcionContract;
use App\Modules\InscripcionesAtenciones\Contracts\TurnosFuturosInscripcionResumen;

/**
 * Pre-chequeo de turnos futuros para la baja (CU-03/FE-01).
 */
final class GetTurnosFuturosQuery
{
    public function __construct(
        private readonly GetInscripcionQuery $detalle,
        private readonly TurnosFuturosInscripcionContract $turnos,
    ) {}

    public function __invoke(int $id, User $actor): TurnosFuturosInscripcionResumen
    {
        ($this->detalle)($id, $actor);

        return $this->turnos->resumen($id);
    }
}
