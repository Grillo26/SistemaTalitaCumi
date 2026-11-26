<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\TurnosFuturosPaqueteContract;

/**
 * Pre-chequeo de turnos futuros, separado de edición y baja.
 */
final class GetTurnosFuturosPaqueteQuery
{
    public function __construct(
        private readonly GetPaqueteQuery $detalle,
        private readonly TurnosFuturosPaqueteContract $turnos,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(int $id, User $actor): array
    {
        ($this->detalle)($id, $actor);

        return $this->turnos->resumen($id)->toArray();
    }
}
