<?php

namespace App\Modules\AgendaTurnos\Queries;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;
use App\Modules\AgendaTurnos\Exceptions\OrigenNoGenerableException;
use App\Modules\AgendaTurnos\Exceptions\PatronNoDisponibleException;
use App\Modules\AgendaTurnos\Exceptions\TurnosYaGeneradosException;
use App\Modules\AgendaTurnos\Rules\OrigenGenerableRule;
use App\Modules\AgendaTurnos\Support\AgendaClock;
use App\Modules\AgendaTurnos\Support\DirectoriosAgenda;
use App\Modules\AgendaTurnos\Support\FeriadosResolver;
use App\Modules\AgendaTurnos\Support\GeneradorTurnos;
use App\Modules\AgendaTurnos\Support\OrigenProgramacion;
use App\Modules\AgendaTurnos\Support\OrigenProgramacionResolver;
use App\Modules\AgendaTurnos\Support\PatronProgramacion;
use App\Modules\AgendaTurnos\Support\ProgramacionGuard;
use App\Modules\AgendaTurnos\Support\TurnosExistentesResolver;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;

/**
 * [Programación de Turnos] RF-003 / RF-004 / RF-005 / RF-006 / RF-009 /
 * RF-014 / RF-015 / CA-002 / CA-003 / CA-004 / CA-005.
 *
 * Cálculo de la previsualización SIN persistir nada. La confirmación
 * recalcula todo: la previsualización nunca es la fuente de verdad.
 */
final class PreviewGeneracionTurnos
{
    public function __construct(
        private readonly OrigenProgramacionResolver $origenes,
        private readonly GeneradorTurnos $calculador,
        private readonly TurnosExistentesResolver $turnos,
        private readonly FeriadosResolver $feriados,
        private readonly DirectoriosAgenda $nombres,
        private readonly AgendaClock $clock,
        private readonly OrigenGenerableRule $generable,
        private readonly ProgramacionGuard $guard,
        private readonly UserBranchAccessContract $branchAccess,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(
        TipoOrigenTurno $tipo,
        int $origenId,
        User $actor,
        bool $usarProfesionalOrigen = true,
        ?int $profesionalId = null,
    ): array {
        $origen = $this->origenes->resolver($tipo, $origenId);

        if (! $this->branchAccess->puedeAcceder((int) $actor->id, $origen->sucursalId)) {
            throw new BranchAccessDeniedException;
        }

        if (! $this->generable->permite($origen->estadoEfectivo)) {
            throw new OrigenNoGenerableException($origen->estadoEfectivo, $tipo);
        }

        if (! $origen->patronDisponible()) {
            throw new PatronNoDisponibleException(
                $origen->patronNoDisponibleMotivo
                    ?? 'La entidad origen no tiene un patrón de días y hora configurado.',
            );
        }

        $activos = $this->turnos->activosDelOrigen($tipo, $origenId);

        if ($activos > 0) {
            throw new TurnosYaGeneradosException($activos);
        }

        $patron = $this->patronEfectivo($origen, $usarProfesionalOrigen, $profesionalId);
        $this->guard->asegurarProfesional($patron->profesionalId, $origen->sucursalId);

        $hoy = $this->clock->hoy();
        $horaActual = $this->clock->horaActual();
        $desde = max($origen->fechaInicio, $hoy);
        $hasta = $origen->fechaFin;

        $calculo = $this->calculador->calcular(
            origen: $origen->conPatron($patron),
            hoy: $hoy,
            horaActual: $horaActual,
            existentes: $this->turnos->conflictivos($origen, $patron->profesionalId, $desde, $hasta),
            feriados: $this->feriados->mapa($origen->sucursalId, $desde, $hasta),
            confirmarAdvertencias: false,
        );

        return [
            'origen' => array_merge($origen->toArray(), [
                'paciente' => [
                    'id' => $origen->pacienteId,
                    'nombre' => $this->nombres->paciente($origen->pacienteId),
                ],
                'servicio' => [
                    'id' => $origen->servicioId,
                    'nombre' => $this->nombres->servicio($origen->servicioId),
                ],
                'sala' => $origen->salaId === null ? null : [
                    'id' => $origen->salaId,
                    'nombre' => $this->nombres->sala($origen->salaId),
                ],
                'profesional' => $patron->profesionalId === null ? null : [
                    'id' => $patron->profesionalId,
                    'nombre' => $this->nombres->profesional($patron->profesionalId),
                ],
            ]),
            'patron' => $patron->toArray(),
            'filas' => $calculo->filasParaRespuesta(),
            'hallazgos' => $calculo->hallazgosParaRespuesta(),
            'resumen' => $calculo->resumen->toArray(),
        ];
    }

    private function patronEfectivo(
        OrigenProgramacion $origen,
        bool $usarProfesionalOrigen,
        ?int $profesionalId,
    ): PatronProgramacion {
        $patron = $origen->patron;

        if ($patron === null) {
            throw new PatronNoDisponibleException;
        }

        if ($usarProfesionalOrigen) {
            return $patron;
        }

        return new PatronProgramacion(
            diasSemana: $patron->diasSemana,
            horaInicio: $patron->horaInicio,
            duracionMinutos: $patron->duracionMinutos,
            profesionalId: $profesionalId,
        );
    }
}
