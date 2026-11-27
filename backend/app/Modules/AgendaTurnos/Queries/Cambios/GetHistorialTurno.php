<?php

namespace App\Modules\AgendaTurnos\Queries\Cambios;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\AccionCambioTurno;
use App\Modules\AgendaTurnos\Exceptions\TurnoNotFoundException;
use App\Modules\AgendaTurnos\Models\BitacoraEntry;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\TurnoHistorialPresenter;

/**
 * [Reprogramación y Cancelación] RF-009 / RN-007 / RNF-012 / CA-006.
 *
 * Historial desde la bitácora inmutable. Incluye reprogramaciones,
 * cancelaciones y, cuando 7.4 las escriba, asistencias/faltas.
 */
final class GetHistorialTurno
{
    public function __construct(
        private readonly AgendaAccessScope $access,
        private readonly TurnoHistorialPresenter $presenter,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(int $turnoId, User $actor): array
    {
        $turno = Turno::query()->find($turnoId);

        if ($turno === null) {
            throw new TurnoNotFoundException($turnoId);
        }

        $this->access->assertSucursal($actor, (int) $turno->sucursal_id);

        $entradas = BitacoraEntry::query()
            ->with('usuario:id,name')
            ->where('entidad', 'turno')
            ->where('entidad_id', $turnoId)
            ->whereIn('accion', AccionCambioTurno::values())
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return [
            'turno_id' => $turnoId,
            'estado_actual' => $turno->estado->value,
            'estado_actual_label' => $turno->estado->label(),
            'items' => $entradas
                ->map(fn (BitacoraEntry $entry): array => $this->presenter->presentar($entry))
                ->all(),
        ];
    }
}
