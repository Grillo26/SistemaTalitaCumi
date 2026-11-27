<?php

namespace App\Modules\AgendaTurnos\Queries;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Support\DirectoriosAgenda;
use App\Modules\AgendaTurnos\Support\OrigenProgramacionResolver;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;

/**
 * [Programación de Turnos] RF-002 / RF-010 / RF-013 / RNF-005.
 *
 * Turnos ya generados de una entidad origen, con su estado y lote.
 */
final class GetTurnosDeOrigen
{
    public function __construct(
        private readonly OrigenProgramacionResolver $origenes,
        private readonly DirectoriosAgenda $nombres,
        private readonly UserBranchAccessContract $branchAccess,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(TipoOrigenTurno $tipo, int $origenId, User $actor): array
    {
        $origen = $this->origenes->resolver($tipo, $origenId);

        if (! $this->branchAccess->puedeAcceder((int) $actor->id, $origen->sucursalId)) {
            throw new BranchAccessDeniedException;
        }

        $query = Turno::query()->ordenado();

        $tipo === TipoOrigenTurno::INSCRIPCION_SALA
            ? $query->where('inscripcion_sala_id', $origenId)
            : $query->where('paquete_sesiones_id', $origenId);

        $turnos = $query->get();

        $pacientes = $this->nombres->pacientes(
            $turnos->pluck('paciente_id')->map(static fn (mixed $id): int => (int) $id)->all(),
        );
        $profesionales = $this->nombres->profesionales(
            $turnos->pluck('profesional_id')
                ->filter()
                ->map(static fn (mixed $id): int => (int) $id)
                ->all(),
        );
        $salas = $this->nombres->salas(
            $turnos->pluck('sala_id')
                ->filter()
                ->map(static fn (mixed $id): int => (int) $id)
                ->all(),
        );
        $servicios = $this->nombres->servicios(
            $turnos->pluck('servicio_id')->map(static fn (mixed $id): int => (int) $id)->all(),
        );

        $data = $turnos->map(static function (Turno $turno) use (
            $pacientes,
            $profesionales,
            $salas,
            $servicios,
        ): array {
            $profesionalId = $turno->profesional_id === null ? null : (int) $turno->profesional_id;
            $salaId = $turno->sala_id === null ? null : (int) $turno->sala_id;

            return [
                'id' => (int) $turno->id,
                'fecha' => $turno->fecha->toDateString(),
                'hora_inicio' => (string) $turno->hora_inicio,
                'hora_fin' => (string) $turno->hora_fin,
                'duracion_minutos' => (int) $turno->duracion_minutos,
                'estado' => $turno->estado->value,
                'estado_label' => $turno->estado->label(),
                'paciente' => [
                    'id' => (int) $turno->paciente_id,
                    'nombre' => $pacientes[(int) $turno->paciente_id] ?? DirectoriosAgenda::NO_DISPONIBLE,
                ],
                'servicio' => [
                    'id' => (int) $turno->servicio_id,
                    'nombre' => $servicios[(int) $turno->servicio_id] ?? DirectoriosAgenda::NO_DISPONIBLE,
                ],
                'sala' => $salaId === null ? null : [
                    'id' => $salaId,
                    'nombre' => $salas[$salaId] ?? DirectoriosAgenda::NO_DISPONIBLE,
                ],
                'profesional' => $profesionalId === null ? null : [
                    'id' => $profesionalId,
                    'nombre' => $profesionales[$profesionalId] ?? DirectoriosAgenda::NO_DISPONIBLE,
                ],
                'lote_generacion_id' => $turno->lote_generacion_id === null
                    ? null
                    : (int) $turno->lote_generacion_id,
                'cancelado_por_lote_id' => $turno->cancelado_por_lote_id === null
                    ? null
                    : (int) $turno->cancelado_por_lote_id,
                'cancelacion_origen' => $turno->cancelacion_origen,
                'cancelado_en' => $turno->cancelado_en?->toIso8601String(),
            ];
        })->all();

        return [
            'origen' => $origen->toArray(),
            'turnos' => $data,
            'total' => count($data),
        ];
    }
}
