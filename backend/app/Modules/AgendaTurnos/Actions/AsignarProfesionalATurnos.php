<?php

namespace App\Modules\AgendaTurnos\Actions;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Events\ProfesionalAsignadoALote;
use App\Modules\AgendaTurnos\Exceptions\AsignacionNoPermitidaException;
use App\Modules\AgendaTurnos\Exceptions\SolapamientoProfesionalException;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Models\TurnoLoteGeneracion;
use App\Modules\AgendaTurnos\Rules\RegeneracionRule;
use App\Modules\AgendaTurnos\Rules\SesionGrupalRule;
use App\Modules\AgendaTurnos\Rules\TurnoSolapamientoRule;
use App\Modules\AgendaTurnos\Support\AgendaClock;
use App\Modules\AgendaTurnos\Support\Hallazgo;
use App\Modules\AgendaTurnos\Support\ProgramacionGuard;
use App\Modules\AgendaTurnos\Support\TurnoCandidato;
use App\Modules\AgendaTurnos\Support\TurnoExistente;
use App\Modules\AgendaTurnos\Support\TurnosExistentesResolver;
use Illuminate\Support\Facades\DB;

/**
 * [Programación de Turnos] RF-007 / RF-008 / RN-005 / CU-03 FA-01.
 *
 * Asigna un profesional a un lote completo o a un subconjunto de turnos.
 * Solo futuros Programados; el lote se aplica todo o nada. No sincroniza el
 * profesional del paquete (eso es propiedad de Inscripciones).
 */
final class AsignarProfesionalATurnos
{
    public function __construct(
        private readonly TurnosExistentesResolver $turnos,
        private readonly TurnoSolapamientoRule $solapamiento,
        private readonly RegeneracionRule $regeneracion,
        private readonly AgendaClock $clock,
        private readonly ProgramacionGuard $guard,
    ) {}

    /**
     * @param  list<int>|null  $turnoIds
     * @return array{profesional_id: int, turno_ids: list<int>, cantidad: int}
     */
    public function __invoke(
        int $profesionalId,
        ?int $loteId,
        ?array $turnoIds,
        User $actor,
        ?string $ip = null,
    ): array {
        return DB::transaction(function () use (
            $profesionalId,
            $loteId,
            $turnoIds,
            $actor,
            $ip,
        ): array {
            $ids = $this->resolverIds($loteId, $turnoIds);

            if ($ids === []) {
                throw new AsignacionNoPermitidaException([
                    ['id' => 0, 'motivo' => 'No hay turnos para asignar.'],
                ]);
            }

            $turnos = $this->turnos->porIds($ids);

            if (count($turnos) !== count($ids)) {
                $encontrados = array_map(
                    static fn (TurnoExistente $turno): int => $turno->id,
                    $turnos,
                );
                $faltantes = array_values(array_diff($ids, $encontrados));

                throw new AsignacionNoPermitidaException(array_map(
                    static fn (int $id): array => [
                        'id' => $id,
                        'motivo' => 'El turno no existe.',
                    ],
                    $faltantes,
                ));
            }

            $hoy = $this->clock->hoy();
            $horaActual = $this->clock->horaActual();

            $noPermitidos = [];

            foreach ($turnos as $turno) {
                if ($turno->estado !== EstadoTurno::PROGRAMADO) {
                    $noPermitidos[] = [
                        'id' => $turno->id,
                        'motivo' => "El turno está en estado {$turno->estado->label()}.",
                    ];

                    continue;
                }

                if (! $this->regeneracion->esFuturo($turno, $hoy, $horaActual)) {
                    $noPermitidos[] = [
                        'id' => $turno->id,
                        'motivo' => 'El turno no es futuro.',
                    ];
                }
            }

            if ($noPermitidos !== []) {
                throw new AsignacionNoPermitidaException($noPermitidos);
            }

            $sucursales = array_values(array_unique(array_map(
                static fn (TurnoExistente $turno): int => $turno->sucursalId,
                $turnos,
            )));

            foreach ($sucursales as $sucursalId) {
                $this->guard->asegurarAcceso($actor, $sucursalId);
                $this->guard->asegurarProfesional($profesionalId, $sucursalId);
            }

            $conflictos = $this->conflictosProfesional($profesionalId, $turnos);

            if ($conflictos !== []) {
                throw new SolapamientoProfesionalException($conflictos);
            }

            Turno::query()
                ->whereIn('id', $ids)
                ->update([
                    'profesional_id' => $profesionalId,
                    'updated_at' => $this->clock->ahora(),
                ]);

            event(new ProfesionalAsignadoALote(
                loteId: $loteId,
                profesionalId: $profesionalId,
                sucursalId: $sucursales[0],
                turnoIds: $ids,
                actorId: (int) $actor->id,
                ip: $ip,
            ));

            return [
                'profesional_id' => $profesionalId,
                'turno_ids' => $ids,
                'cantidad' => count($ids),
            ];
        });
    }

    /**
     * @param  list<int>|null  $turnoIds
     * @return list<int>
     */
    private function resolverIds(?int $loteId, ?array $turnoIds): array
    {
        if ($turnoIds !== null && $turnoIds !== []) {
            return array_values(array_unique(array_map(
                static fn (mixed $id): int => (int) $id,
                $turnoIds,
            )));
        }

        if ($loteId === null) {
            return [];
        }

        $lote = TurnoLoteGeneracion::query()->find($loteId);

        if ($lote === null) {
            return [];
        }

        return $lote->idsCreados();
    }

    /**
     * @param  list<TurnoExistente>  $turnos
     * @return list<Hallazgo>
     */
    private function conflictosProfesional(int $profesionalId, array $turnos): array
    {
        $fechas = array_map(
            static fn (TurnoExistente $turno): string => $turno->fecha,
            $turnos,
        );
        $desde = min($fechas);
        $hasta = max($fechas);
        $ids = array_map(
            static fn (TurnoExistente $turno): int => $turno->id,
            $turnos,
        );

        $existentes = $this->turnos->delProfesional($profesionalId, $desde, $hasta, $ids);
        $conflictos = [];

        foreach ($turnos as $turno) {
            $candidato = new TurnoCandidato(
                pacienteId: $turno->pacienteId,
                profesionalId: $profesionalId,
                salaId: $turno->salaId,
                servicioId: $turno->servicioId,
                fecha: $turno->fecha,
                horaInicio: $turno->horaInicio,
                horaFin: $turno->horaFin,
                sesionKey: SesionGrupalRule::clave(
                    $turno->salaId,
                    $turno->fecha,
                    $turno->horaInicio,
                    $turno->horaFin,
                    $turno->servicioId,
                ),
            );

            foreach ($this->solapamiento->evaluar($candidato, $existentes) as $hallazgo) {
                if ($hallazgo->tipo === 'profesional') {
                    $conflictos[] = $hallazgo;
                }
            }
        }

        return $conflictos;
    }
}
